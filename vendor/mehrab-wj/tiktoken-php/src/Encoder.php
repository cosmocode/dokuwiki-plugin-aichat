<?php

namespace TikToken;

class Encoder
{
    private bool $initialized = false;

    /** @var array<string> */
    private array $bpeCache = [];

    /** @var array<string> */
    private array $rawCharacters = [];

    /** @var array<string> */
    private array $encoder = [];

    /** @var array<array<int>> */
    private array $bpeRanks = [];

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }
        $rawCharacters = file_get_contents(__DIR__.'/../data/characters.json');
        if (false === $rawCharacters) {
            throw new \RuntimeException('Unable to load characters.json');
        }
        $this->rawCharacters = json_decode($rawCharacters, true, 512, JSON_THROW_ON_ERROR);

        $encoder = file_get_contents(__DIR__.'/../data/encoder.json');
        if (false === $encoder) {
            throw new \RuntimeException('Unable to load encoder.json');
        }
        $this->encoder = json_decode($encoder, true, 512, JSON_THROW_ON_ERROR);

        $bpeDictionary = file_get_contents(__DIR__.'/../data/vocab.bpe');
        if (false === $bpeDictionary) {
            throw new \RuntimeException('Unable to load vocab.bpe');
        }

        $lines = preg_split('#\r\n|\r|\n#', $bpeDictionary);
        if (false === $lines) {
            throw new \RuntimeException('Unable to split vocab.bpe');
        }

        $bpeMerges = [];
        $rawDictionaryLines = array_slice($lines, 1, count($lines), true);
        foreach ($rawDictionaryLines as $rawDictionaryLine) {
            $splitLine = preg_split('#(\s+)#', (string) $rawDictionaryLine);
            if (false === $splitLine) {
                continue;
            }
            $splitLine = array_filter($splitLine, $this->filterEmpty(...));
            if ([] !== $splitLine) {
                $bpeMerges[] = $splitLine;
            }
        }

        $this->bpeRanks = $this->buildBpeRanks($bpeMerges);
        $this->initialized = true;
    }

    /** @return array<string> */
    public function encode(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $this->initialize();

        preg_match_all("#'s|'t|'re|'ve|'m|'ll|'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+#u", $text, $matches);
        if (!isset($matches[0]) || 0 == (is_countable($matches[0]) ? count($matches[0]) : 0)) {
            return [];
        }

        $bpeTokens = [];
        foreach ($matches[0] as $token) {
            $token = mb_convert_encoding((string) $token, "UTF-8", "ISO-8859-1");
            $characters = mb_str_split($token, 1, 'UTF-8');

            $resultWord = '';
            foreach ($characters as $char) {
                if (!isset($this->rawCharacters[$this->characterToUnicode($char)])) {
                    continue;
                }
                $resultWord .= $this->rawCharacters[$this->characterToUnicode($char)];
            }

            $newTokensBpe = $this->bpe($resultWord);
            $newTokensBpe = explode(' ', $newTokensBpe);
            foreach ($newTokensBpe as $newBpeToken) {
                $encoded = $this->encoder[$newBpeToken] ?? $newBpeToken;
                if (isset($bpeTokens[$newBpeToken])) {
                    $bpeTokens[] = $encoded;
                } else {
                    $bpeTokens[$newBpeToken] = $encoded;
                }
            }
        }

        return array_values($bpeTokens);
    }

    private function filterEmpty(mixed $var): bool
    {
        return null !== $var && false !== $var && '' !== $var;
    }

    private function characterToUnicode(string $characters): int
    {
        $firstCharacterCode = ord($characters[0]);

        if ($firstCharacterCode <= 127) {
            return $firstCharacterCode;
        }

        if ($firstCharacterCode >= 192 && $firstCharacterCode <= 223) {
            return ($firstCharacterCode - 192) * 64 + (ord($characters[1]) - 128);
        }

        if ($firstCharacterCode >= 224 && $firstCharacterCode <= 239) {
            return ($firstCharacterCode - 224) * 4096 + (ord($characters[1]) - 128) * 64 + (ord($characters[2]) - 128);
        }

        if ($firstCharacterCode >= 240 && $firstCharacterCode <= 247) {
            return ($firstCharacterCode - 240) * 262144 + (ord($characters[1]) - 128) * 4096 + (ord($characters[2]) - 128) * 64 + (ord($characters[3]) - 128);
        }

        if ($firstCharacterCode >= 248 && $firstCharacterCode <= 251) {
            return ($firstCharacterCode - 248) * 16_777_216 + (ord($characters[1]) - 128) * 262144 + (ord($characters[2]) - 128) * 4096 + (ord($characters[3]) - 128) * 64 + (ord($characters[4]) - 128);
        }

        if ($firstCharacterCode >= 252 && $firstCharacterCode <= 253) {
            return ($firstCharacterCode - 252) * 1_073_741_824 + (ord($characters[1]) - 128) * 16_777_216 + (ord($characters[2]) - 128) * 262144 + (ord($characters[3]) - 128) * 4096 + (ord($characters[4]) - 128) * 64 + (ord($characters[5]) - 128);
        }

        if ($firstCharacterCode >= 254) {
            return 0;
        }

        return 0;
    }

    /**
     * @param array<array<mixed>> $bpes
     *
     * @return array<array<int>>
     */
    private function buildBpeRanks(array $bpes): array
    {
        $result = [];
        $rank = 0;
        foreach ($bpes as $bpe) {
            if (!isset($bpe[1], $bpe[0])) {
                continue;
            }

            $result[$bpe[0]][$bpe[1]] = $rank;
            ++$rank;
        }

        return $result;
    }

    /**
     * Return set of symbol pairs in a word.
     * Word is represented as tuple of symbols (symbols being variable-length strings).
     *
     * @param array<int, string> $word
     *
     * @return mixed[]
     */
    private function buildSymbolPairs(array $word): array
    {
        $pairs = [];
        $previousPart = null;
        foreach ($word as $i => $part) {
            if ($i > 0) {
                $pairs[] = [$previousPart, $part];
            }

            $previousPart = $part;
        }

        return $pairs;
    }

    private function bpe(string $token): string
    {
        if (isset($this->bpeCache[$token])) {
            return $this->bpeCache[$token];
        }

        $word = mb_str_split($token, 1, 'UTF-8');
        $initialLength = count($word);
        $pairs = $this->buildSymbolPairs($word);
        if ([] === $pairs) {
            return $token;
        }

        while (true) {
            $minPairs = [];
            foreach ($pairs as $pair) {
                if (isset($this->bpeRanks[$pair[0]][$pair[1]])) {
                    $rank = $this->bpeRanks[$pair[0]][$pair[1]];
                    $minPairs[$rank] = $pair;
                } else {
                    $minPairs[10e10] = $pair;
                }
            }

            $minPairsKeys = array_keys($minPairs);
            sort($minPairsKeys, SORT_NUMERIC);
            $minimumKey = $minPairsKeys[0] ?? null;

            $bigram = $minPairs[$minimumKey];
            if (!isset($this->bpeRanks[$bigram[0]][$bigram[1]])) {
                break;
            }

            $first = $bigram[0];
            $second = $bigram[1];
            $newWord = [];
            $i = 0;
            while ($i < count($word)) {
                $j = $this->indexOf($word, $first, $i);
                if (-1 === $j) {
                    $newWord = [
                        ...$newWord,
                        ...array_slice($word, $i, null, true),
                    ];
                    break;
                }

                $slicer = $i > $j || 0 === $j ? [] : array_slice($word, $i, $j - $i, true);

                $newWord = [
                    ...$newWord,
                    ...$slicer,
                ];
                if (count($newWord) > $initialLength) {
                    break;
                }

                $i = $j;
                if ($word[$i] === $first && $i < count($word) - 1 && $word[$i + 1] === $second) {
                    $newWord[] = $first.$second;
                    $i += 2;
                } else {
                    $newWord[] = $word[$i];
                    ++$i;
                }
            }

            if ($word === $newWord) {
                break;
            }

            $word = $newWord;
            if (1 === count($word)) {
                break;
            }

            $pairs = $this->buildSymbolPairs($word);
        }

        $word = implode(' ', $word);
        $this->bpeCache[$token] = $word;

        return $word;
    }

    /**
     * @param array<int, string> $array
     */
    private function indexOf(array $array, string $searchElement, int $fromIndex): int
    {
        $slicedArray = array_slice($array, $fromIndex, preserve_keys: true);

        $indexed = array_search($searchElement, $slicedArray);

        return false === $indexed ? -1 : $indexed;
    }
}
