<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\Search\Indexer;
use Hexogen\KDTree\FSKDTree;
use Hexogen\KDTree\FSTreePersister;
use Hexogen\KDTree\Item;
use Hexogen\KDTree\ItemFactory;
use Hexogen\KDTree\ItemList;
use Hexogen\KDTree\KDTree;
use Hexogen\KDTree\NearestSearch;
use Hexogen\KDTree\Point;
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

    protected $openAI;

    public function __construct(OpenAI $openAI, $logger = null)
    {
        $this->openAI = $openAI;
        $this->logger = $logger;
    }

    public function createNewIndex()
    {
        io_rmdir($this->getStorageDir(), true); // delete old index

        $indexer = new Indexer();
        $pages = $indexer->getPages();
        $itemCount = 0;

        $itemList = new ItemList(1536);
        foreach ($pages as $page) {
            if (!page_exists($page)) continue;
            $text = rawWiki($page);
            $chunks = $this->splitIntoChunks($text);
            $meta = [
                'pageid' => $page,
                // fixme add title here?
            ];
            foreach ($chunks as $chunk) {
                $embedding = $this->openAI->getEmbedding($chunk);
                $item = new Item($itemCount++, $embedding);
                $itemList->addItem($item);
                $this->saveChunk($item->getId(), $chunk, $meta);
            }
            if ($this->logger) {
                $this->logger->success('Split {id} into {count} chunks', ['id' => $page, 'count' => count($chunks)]);
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

        $file = $this->getStorageDir() . self::INDEX_FILE;
        $fsTree = new FSKDTree($file, new ItemFactory());
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
     * @param $text
     * @return array
     * @throws \Exception
     * @todo maybe add overlap support
     * @todo support splitting too long sentences
     */
    protected function splitIntoChunks($text)
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
                throw new \Exception('Sentence too long, splitting not implemented yet');
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
     * @param array $meta meta data to store with the chunk
     * @return void
     */
    public function saveChunk($id, $text, $meta = [])
    {
        $data = [
            'id' => $id,
            'text' => $text,
            'meta' => $meta,
        ];

        $chunkfile = $this->getStorageDir('chunk') . $id . '.json';
        io_saveFile($chunkfile, json_encode($data));
    }

    /**
     * Load chunk data from the file system
     *
     * @param int $id
     * @return array The chunk data [id, text, meta => []]
     */
    public function loadChunk($id)
    {
        $chunkfile = $this->getStorageDir('chunk') . $id . '.json';
        return json_decode(io_readFile($chunkfile, false), true);
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
