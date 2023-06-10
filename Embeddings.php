<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\Search\Indexer;
use Hexogen\KDTree\Exception\ValidationException;
use Hexogen\KDTree\FSKDTree;
use Hexogen\KDTree\FSTreePersister;
use Hexogen\KDTree\Item;
use Hexogen\KDTree\ItemFactory;
use Hexogen\KDTree\ItemList;
use Hexogen\KDTree\KDTree;
use Hexogen\KDTree\NearestSearch;
use Hexogen\KDTree\Point;
use splitbrain\phpcli\CLI;
use TikToken\Encoder;
use Vanderlee\Sentence\Sentence;

/**
 * Manage the embeddings index
 *
 * Pages are split into chunks of 1000 tokens each. For each chunk the embedding vector is fetched from
 * OpenAI and stored in a K-D Tree, chunk data is written to the file system.
 */
class Embeddings
{

    const MAX_TOKEN_LEN = 1000;
    const INDEX_NAME = 'aichat';
    const INDEX_FILE = 'index.bin';

    /** @var OpenAI */
    protected $openAI;
    /** @var CLI|null */
    protected $logger;

    /**
     * @param OpenAI $openAI
     */
    public function __construct(OpenAI $openAI)
    {
        $this->openAI = $openAI;
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

        $itemList = new ItemList(1536);
        foreach ($pages as $pid => $page) {
            if (!page_exists($page)) continue;
            if (isHiddenPage($page)) continue;
            if ($skipRE && preg_match($skipRE, $page)) continue;

            $chunkID = $pid * 100; // chunk IDs start at page ID * 100

            $firstChunk = $this->getChunkFilePath($chunkID);
            if (@filemtime(wikiFN($page)) < @filemtime($firstChunk)) {
                // page is older than the chunks we have, reuse the existing chunks
                $this->reusePageChunks($itemList, $page, $chunkID);
            } else {
                // page is newer than the chunks we have, create new chunks
                $this->deletePageChunks($chunkID);
                $this->createPageChunks($itemList, $page, $chunkID);
            }
        }

        $tree = new KDTree($itemList);
        if ($this->logger) {
            $this->logger->success('Created index with {count} items', ['count' => $tree->getItemCount()]);
        }
        $persister = new FSTreePersister($this->getStorageDir());
        $persister->convert($tree, self::INDEX_FILE);
    }

    /**
     * Split the given page, fetch embedding vectors, save chunks and add them to the tree list
     *
     * @param ItemList $itemList The list to add the items to
     * @param string $page Name of the page to split
     * @param int $chunkID The ID of the first chunk of this page
     * @return void
     * @throws \Exception
     */
    protected function createPageChunks(ItemList $itemList, $page, $chunkID)
    {
        $text = rawWiki($page);
        $chunks = $this->splitIntoChunks($text);
        $meta = [
            'pageid' => $page,
        ];
        foreach ($chunks as $chunk) {
            try {
                $embedding = $this->openAI->getEmbedding($chunk);
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error(
                        'Failed to get embedding for chunk of page {page}: {msg}',
                        ['page' => $page, 'msg' => $e->getMessage()]
                    );
                }
                continue;
            }
            $item = new Item($chunkID, $embedding);
            $itemList->addItem($item);
            $this->saveChunk($item->getId(), $chunk, $embedding, $meta);
            $chunkID++;
        }
        if ($this->logger) {
            $this->logger->success('{id} split into {count} chunks', ['id' => $page, 'count' => count($chunks)]);
        }
    }

    /**
     * Load the existing chunks for the given page and add them to the tree list
     *
     * @param ItemList $itemList The list to add the items to
     * @param string $page Name of the page to split
     * @param int $chunkID The ID of the first chunk of this page
     * @return void
     */
    protected function reusePageChunks(ItemList $itemList, $page, $chunkID)
    {
        for ($i = 0; $i < 100; $i++) {
            $chunk = $this->loadChunk($chunkID + $i);
            if (!$chunk) break;
            $item = new Item($chunkID, $chunk['embedding']);
            $itemList->addItem($item);
        }
        if ($this->logger) {
            $this->logger->success('{id} reused {count} chunks', ['id' => $page, 'count' => $i]);
        }
    }

    /**
     * Delete all possibly existing chunks for one page (identified by the first chunk ID)
     *
     * @param int $chunkID The ID of the first chunk of this page
     * @return void
     */
    protected function deletePageChunks($chunkID)
    {
        for ($i = 0; $i < 100; $i++) {
            $chunk = $this->getChunkFilePath($chunkID + $i);
            if (!file_exists($chunk)) break;
            unlink($chunk);
        }
    }

    /**
     * Do a nearest neighbor search for chunks similar to the given question
     *
     * Returns only chunks the current user is allowed to read, may return an empty result.
     *
     * @param string $query The question
     * @param int $limit The number of results to return
     * @return array
     * @throws \Exception
     */
    public function getSimilarChunks($query, $limit = 4)
    {
        global $auth;
        $embedding = $this->openAI->getEmbedding($query);

        $fsTree = $this->getTree();
        $fsSearcher = new NearestSearch($fsTree);
        $items = $fsSearcher->search(new Point($embedding), $limit * 2); // we get twice as many as needed

        $result = [];
        foreach ($items as $item) {
            $chunk = $this->loadChunk($item->getId());
            // filter out chunks the user is not allowed to read
            if ($auth && auth_quickaclcheck($chunk['meta']['pageid']) < AUTH_READ) continue;
            $result[] = $chunk;
            if (count($result) >= $limit) break;
        }
        return $result;
    }

    /**
     * Access to the KD Tree
     *
     * @return FSKDTree
     */
    public function getTree()
    {
        $file = $this->getStorageDir() . self::INDEX_FILE;
        return new FSKDTree($file, new ItemFactory());
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

    /**
     * Store additional chunk data in the file system
     *
     * @param int $id The chunk id in the K-D tree
     * @param string $text raw text of the chunk
     * @param float[] $embedding embedding vector of the chunk
     * @param array $meta meta data to store with the chunk
     * @return void
     */
    public function saveChunk($id, $text, $embedding, $meta = [])
    {
        $data = [
            'id' => $id,
            'text' => $text,
            'embedding' => $embedding,
            'meta' => $meta,
        ];

        $chunkfile = $this->getChunkFilePath($id);
        io_saveFile($chunkfile, json_encode($data));
    }

    /**
     * Load chunk data from the file system
     *
     * @param int $id
     * @return array|false The chunk data [id, text, embedding, meta => []], false if not found
     */
    public function loadChunk($id)
    {
        $chunkfile = $this->getChunkFilePath($id);
        if (!file_exists($chunkfile)) return false;
        return json_decode(io_readFile($chunkfile, false), true);
    }

    /**
     * Return the path to the chunk file
     *
     * @param $id
     * @return string
     */
    protected function getChunkFilePath($id)
    {
        $id = dechex($id); // use hexadecimal for shorter file names
        return $this->getStorageDir('chunk') . $id . '.json';
    }

    /**
     * Return the path to where the K-D tree and chunk data is stored
     *
     * @param string $subdir
     * @return string
     */
    protected function getStorageDir($subdir = '')
    {
        global $conf;
        $dir = $conf['indexdir'] . '/' . self::INDEX_NAME . '/';
        if ($subdir) $dir .= $subdir . '/';
        io_mkdir_p($dir);
        return $dir;
    }
}
