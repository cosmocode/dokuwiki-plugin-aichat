<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\plugin\aichat\Model\AbstractModel;
use dokuwiki\plugin\aichat\Storage\AbstractStorage;
use dokuwiki\Search\Indexer;
use splitbrain\phpcli\CLI;
use TikToken\Encoder;
use Vanderlee\Sentence\Sentence;

/**
 * Manage the embeddings index
 *
 * Pages are split into chunks of 1000 tokens each. For each chunk the embedding vector is fetched from
 * OpenAI and stored in the Storage backend.
 */
class Embeddings
{
    /** @var int maximum overlap between chunks in tokens */
    const MAX_OVERLAP_LEN = 200;

    /** @var AbstractModel */
    protected $model;
    /** @var CLI|null */
    protected $logger;
    /** @var Encoder */
    protected $tokenEncoder;

    /** @var AbstractStorage */
    protected $storage;

    /** @var array remember sentences when chunking */
    private $sentenceQueue = [];

    /**
     * @param AbstractModel $model
     */
    public function __construct(AbstractModel $model, AbstractStorage $storage)
    {
        $this->model = $model;
        $this->storage = $storage;
    }

    /**
     * Access storage
     *
     * @return AbstractStorage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Add a logger instance
     *
     * @param CLI $logger
     * @return void
     */
    public function setLogger(CLI $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the token encoder instance
     *
     * @return Encoder
     */
    public function getTokenEncoder()
    {
        if ($this->tokenEncoder === null) {
            $this->tokenEncoder = new Encoder();
        }
        return $this->tokenEncoder;
    }

    /**
     * Update the embeddings storage
     *
     * @param string $skipRE Regular expression to filter out pages (full RE with delimiters)
     * @param bool $clear Should any existing storage be cleared before updating?
     * @return void
     * @throws \Exception
     */
    public function createNewIndex($skipRE = '', $clear = false)
    {
        $indexer = new Indexer();
        $pages = $indexer->getPages();

        $this->storage->startCreation($clear);
        foreach ($pages as $pid => $page) {
            $chunkID = $pid * 100; // chunk IDs start at page ID * 100

            if (
                !page_exists($page) ||
                isHiddenPage($page) ||
                filesize(wikiFN($page)) < 150 || // skip very small pages
                ($skipRE && preg_match($skipRE, $page))
            ) {
                // this page should not be in the index (anymore)
                $this->storage->deletePageChunks($page, $chunkID);
                continue;
            }

            $firstChunk = $this->storage->getChunk($chunkID);
            if ($firstChunk && @filemtime(wikiFN($page)) < $firstChunk->getCreated()) {
                // page is older than the chunks we have, reuse the existing chunks
                $this->storage->reusePageChunks($page, $chunkID);
                if ($this->logger) $this->logger->info("Reusing chunks for $page");
            } else {
                // page is newer than the chunks we have, create new chunks
                $this->storage->deletePageChunks($page, $chunkID);
                $this->storage->addPageChunks($this->createPageChunks($page, $chunkID));
            }
        }
        $this->storage->finalizeCreation();
    }

    /**
     * Split the given page, fetch embedding vectors and return Chunks
     *
     * Will use the text renderer plugin if available to get the rendered text.
     * Otherwise the raw wiki text is used.
     *
     * @param string $page Name of the page to split
     * @param int $firstChunkID The ID of the first chunk of this page
     * @return Chunk[] A list of chunks created for this page
     * @throws \Exception
     */
    protected function createPageChunks($page, $firstChunkID)
    {
        $chunkList = [];

        $textRenderer = plugin_load('renderer', 'text');
        if ($textRenderer) {
            global $ID;
            $ID = $page;
            $text = p_cached_output(wikiFN($page), 'text', $page);
        } else {
            $text = rawWiki($page);
        }

        $parts = $this->splitIntoChunks($text);
        foreach ($parts as $part) {
            if (trim($part) == '') continue; // skip empty chunks

            try {
                $embedding = $this->model->getEmbedding($part);
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error(
                        'Failed to get embedding for chunk of page {page}: {msg}',
                        ['page' => $page, 'msg' => $e->getMessage()]
                    );
                }
                continue;
            }
            $chunkList[] = new Chunk($page, $firstChunkID, $part, $embedding);
            $firstChunkID++;
        }
        if ($this->logger) {
            if (count($chunkList)) {
                $this->logger->success('{id} split into {count} chunks', ['id' => $page, 'count' => count($chunkList)]);
            } else {
                $this->logger->warning('{id} could not be split into chunks', ['id' => $page]);
            }
        }
        return $chunkList;
    }

    /**
     * Do a nearest neighbor search for chunks similar to the given question
     *
     * Returns only chunks the current user is allowed to read, may return an empty result.
     * The number of returned chunks depends on the MAX_CONTEXT_LEN setting.
     *
     * @param string $query The question
     * @return Chunk[]
     * @throws \Exception
     */
    public function getSimilarChunks($query)
    {
        global $auth;
        $vector = $this->model->getEmbedding($query);

        $fetch = ceil(
            ($this->model->getMaxContextTokenLength() / $this->model->getMaxEmbeddingTokenLength())
            * 1.5 // fetch a few more than needed, since not all chunks are maximum length
        );
        $chunks = $this->storage->getSimilarChunks($vector, $fetch);

        $size = 0;
        $result = [];
        foreach ($chunks as $chunk) {
            // filter out chunks the user is not allowed to read
            if ($auth && auth_quickaclcheck($chunk->getPage()) < AUTH_READ) continue;

            $chunkSize = count($this->getTokenEncoder()->encode($chunk->getText()));
            if ($size + $chunkSize > $this->model->getMaxContextTokenLength()) break; // we have enough

            $result[] = $chunk;
            $size += $chunkSize;
        }
        return $result;
    }


    /**
     * @param $text
     * @return array
     * @throws \Exception
     * @todo support splitting too long sentences
     */
    public function splitIntoChunks($text)
    {
        $sentenceSplitter = new Sentence();
        $tiktok = $this->getTokenEncoder();

        $chunks = [];
        $sentences = $sentenceSplitter->split($text);

        $chunklen = 0;
        $chunk = '';
        while ($sentence = array_shift($sentences)) {
            $slen = count($tiktok->encode($sentence));
            if ($slen > $this->model->getMaxEmbeddingTokenLength()) {
                // sentence is too long, we need to split it further
                if ($this->logger) $this->logger->warning('Sentence too long, splitting not implemented yet');
                continue;
            }

            if ($chunklen + $slen < $this->model->getMaxEmbeddingTokenLength()) {
                // add to current chunk
                $chunk .= $sentence;
                $chunklen += $slen;
                // remember sentence for overlap check
                $this->rememberSentence($sentence);
            } else {
                // add current chunk to result
                $chunks[] = $chunk;

                // start new chunk with remembered sentences
                $chunk = join(' ', $this->sentenceQueue);
                $chunk .= $sentence;
                $chunklen = count($tiktok->encode($chunk));
            }
        }
        $chunks[] = $chunk;

        return $chunks;
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
        $encoder = $this->getTokenEncoder();
        while (count($encoder->encode(join(' ', $this->sentenceQueue))) > self::MAX_OVERLAP_LEN) {
            array_shift($this->sentenceQueue);
        }
    }
}
