<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\Utf8\PhpString;
use TikToken\Encoder;
use Vanderlee\Sentence\Sentence;

/**
 * Class to split text into chunks of a given size in tokens
 *
 * Prefers to split at sentence boundaries, but will split long sentences if necessary.
 * Also keeps some overlap between chunks to preserve context.
 */
class TextSplitter
{
    /** @var int maximum overlap between chunks in tokens */
    final public const MAX_OVERLAP_LEN = 200;

    protected int $chunkSize;
    protected Encoder $tiktok;
    protected array $sentenceQueue = [];

    /**
     * Constructor
     *
     * @param int $chunksize maximum chunk size in tokens
     * @param Encoder $tiktok token encoder
     */
    public function __construct(int $chunksize, Encoder $tiktok)
    {
        $this->chunkSize = $chunksize;
        $this->tiktok = $tiktok;
    }

    /**
     * Split the given text into chunks of the configured size
     *
     * @param string $text
     * @return string[]
     */
    public function splitIntoChunks(string $text): array
    {
        $this->sentenceQueue = []; // reset sentence queue
        $chunks = [];

        $sentenceSplitter = new Sentence();
        $sentences = $sentenceSplitter->split($text);

        $chunklen = 0;
        $chunk = '';
        while ($sentence = array_shift($sentences)) {
            $slen = count($this->tiktok->encode($sentence));
            if ($slen > $this->chunkSize) {
                // Sentence is too long, split into smaller parts and push the results back to the front of the queue
                array_unshift($sentences, ...$this->splitLongSentence($sentence));
                continue;
            }

            if ($chunklen + $slen < $this->chunkSize) {
                // add to current chunk
                $chunk .= $sentence;
                $chunklen += $slen;
                // remember sentence for overlap check
                $this->rememberSentence($sentence);
            } else {
                // add current chunk to result
                $chunk = trim($chunk);
                if ($chunk !== '') $chunks[] = $chunk;

                // start new chunk with remembered sentences
                $chunk = implode(' ', $this->sentenceQueue);
                $chunk .= $sentence;
                $chunklen = count($this->tiktok->encode($chunk));
            }
        }

        // Add the last chunk if not empty
        $chunk = trim($chunk);
        if ($chunk !== '') $chunks[] = $chunk;

        return $chunks;
    }

    /**
     * Force splitting of a too long sentence into smaller parts, preferably at word boundaries
     *
     * @param string $sentence
     * @return string[]
     */
    protected function splitLongSentence(string $sentence): array
    {
        $chunkSize = $this->chunkSize / 4; // when force splitting, make sentences a quarter of the chunk size

        // Try naive approach first: split by spaces
        $words = preg_split('/(\b+)/', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE);
        $subSentences = [];
        $currentSubSentence = '';
        $currentSubSentenceLen = 0;

        foreach ($words as $word) {
            $wordLen = count($this->tiktok->encode($word));

            if ($wordLen > $chunkSize) {
                // word is too long, probably no spaces, split it further
                array_merge($subSentences, $this->splitString($word, $wordLen, $chunkSize));
            } elseif ($currentSubSentenceLen + $wordLen < $chunkSize) {
                // Add to current sub-sentence
                $currentSubSentence .= $word;
                $currentSubSentenceLen += $wordLen;
            } else {
                // Add current sub-sentence to result
                $subSentences[] = $currentSubSentence;
                // Start new sub-sentence
                $currentSubSentence = $word;
                $currentSubSentenceLen = $wordLen;
            }
        }

        // Add last sub-sentence to result
        $subSentences[] = $currentSubSentence;

        return $subSentences;
    }

    /**
     * Split a string into smaller parts of approximately the given size
     * This is a naive split that does not care about word boundaries
     *
     * @param string $text text to split
     * @param int $tokenlength length of the text in tokens
     * @param int $chunksize desired chunk size in tokens
     * @return string[]
     */
    protected function splitString(string $text, int $tokenlength, int $chunksize): array
    {
        $numPieces = ceil($tokenlength / $chunksize);
        $pieceLength = ceil(PhpString::strlen($text) / $numPieces);

        // utf8 aware split
        $pieces = [];
        for ($i = 0; $i < $numPieces; $i++) {
            $pieces[] = PhpString::substr($text, $i * $pieceLength, $pieceLength);
        }
        return $pieces;
    }

    /**
     * Add a sentence to the queue of remembered sentences
     *
     * @param string $sentence
     * @return void
     */
    protected function rememberSentence($sentence)
    {
        // add sentence to queue
        $this->sentenceQueue[] = $sentence;

        // remove oldest sentences from queue until we are below the max overlap
        while (count($this->tiktok->encode(implode(' ', $this->sentenceQueue))) > self::MAX_OVERLAP_LEN) {
            array_shift($this->sentenceQueue);
        }
    }
}
