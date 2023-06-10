<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\plugin\aichat\backend\AbstractStorage;
use dokuwiki\plugin\aichat\backend\Chunk;
use dokuwiki\plugin\aichat\backend\KDTreeStorage;
use dokuwiki\plugin\aichat\backend\SQLiteStorage;
use dokuwiki\Search\Indexer;
use Hexogen\KDTree\Exception\ValidationException;
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

    const MAX_TOKEN_LEN = 1000;


    /** @var OpenAI */
    protected $openAI;
    /** @var CLI|null */
    protected $logger;

    /** @var AbstractStorage */
    protected $storage;

    /**
     * @param OpenAI $openAI
     */
    public function __construct(OpenAI $openAI)
    {
        $this->openAI = $openAI;
        //$this->storage = new KDTreeStorage(); // FIXME make configurable
        $this->storage = new SQLiteStorage(); // FIXME make configurable
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
     * Create a new K-D Tree from all pages
     *
     * Deletes the existing index
     *
     * @param string $skipRE Regular expression to filter out pages (full RE with delimiters)
     * @return void
     * @throws ValidationException
     */
    public function createNewIndex($skipRE = '')
    {
        $indexer = new Indexer();
        $pages = $indexer->getPages();

        $this->storage->startCreation(1536);
        foreach ($pages as $pid => $page) {
            if (!page_exists($page)) continue;
            if (isHiddenPage($page)) continue;
            if ($skipRE && preg_match($skipRE, $page)) continue; // FIXME delete previous chunks

            $chunkID = $pid * 100; // chunk IDs start at page ID * 100

            $firstChunk = $this->storage->getChunk($chunkID);
            if ($firstChunk && @filemtime(wikiFN($page)) < $firstChunk->getCreated()) {
                // page is older than the chunks we have, reuse the existing chunks
                $this->storage->reusePageChunks($page, $chunkID);
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
     * @param string $page Name of the page to split
     * @param int $firstChunkID The ID of the first chunk of this page
     * @return Chunk[] A list of chunks created for this page
     * @throws \Exception
     */
    protected function createPageChunks($page, $firstChunkID)
    {
        $chunkList = [];
        $parts = $this->splitIntoChunks(rawWiki($page));
        foreach ($parts as $part) {
            try {
                $embedding = $this->openAI->getEmbedding($part);
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
            $this->logger->success('{id} split into {count} chunks', ['id' => $page, 'count' => count($parts)]);
        }
        return $chunkList;
    }

    /**
     * Do a nearest neighbor search for chunks similar to the given question
     *
     * Returns only chunks the current user is allowed to read, may return an empty result.
     *
     * @param string $query The question
     * @param int $limit The number of results to return
     * @return Chunk[]
     * @throws \Exception
     */
    public function getSimilarChunks($query, $limit = 4)
    {
        global $auth;
        $vector = $this->openAI->getEmbedding($query);

        $chunks = $this->storage->getSimilarChunks($vector, $limit);
        $result = [];
        foreach ($chunks as $chunk) {
            // filter out chunks the user is not allowed to read
            if ($auth && auth_quickaclcheck($chunk->getPage()) < AUTH_READ) continue;
            $result[] = $chunk;
            if (count($result) >= $limit) break;
        }
        return $result;
    }


    /**
     * @param $text
     * @return array
     * @throws \Exception
     * @todo maybe add overlap support
     * @todo support splitting too long sentences
     */
    public function splitIntoChunks($text)
    {
        $sentenceSplitter = new Sentence();
        $tiktok = new Encoder();

        $chunks = [];
        $sentences = $sentenceSplitter->split($text);

        $chunklen = 0;
        $chunk = '';
        while ($sentence = array_shift($sentences)) {
            $slen = count($tiktok->encode($sentence));
            if ($slen > self::MAX_TOKEN_LEN) {
                // sentence is too long, we need to split it further
                if ($this->logger) $this->logger->warning('Sentence too long, splitting not implemented yet');
                continue;
            }

            if ($chunklen + $slen < self::MAX_TOKEN_LEN) {
                // add to current chunk
                $chunk .= $sentence;
                $chunklen += $slen;
            } else {
                // start new chunk
                $chunks[] = $chunk;
                $chunk = $sentence;
                $chunklen = $slen;
            }
        }
        $chunks[] = $chunk;

        return $chunks;
    }
}
