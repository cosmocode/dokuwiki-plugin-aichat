<?php

namespace dokuwiki\plugin\aichat;

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
     * Force splitting of a too long sentence into smaller parts
     *
     * @param string $sentence
     * @return string[]
     */
    protected function splitLongSentence($sentence)
    {
        $chunkSize = $this->chunkSize / 4; // when force splitting, make sentences a quarter of the chunk size

        // Try naive approach first: split by spaces
        $words = preg_split('/(\s+)/', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE);
        $subSentences = [];
        $currentSubSentence = '';
        $currentSubSentenceLen = 0;

        foreach ($words as $word) {
            $wordLen = count($this->tiktok->encode($word));

            if ($wordLen > $chunkSize) {
                // If a single word is too long, split it into smaller chunks
                $wordChunks = str_split($word, $chunkSize); // Split into smaller parts //FIXME this splitting should be done by tokens, not by characters
                foreach ($wordChunks as $chunk) {
                    $subSentences[] = $chunk;
                }
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
