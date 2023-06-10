<?php


namespace dokuwiki\plugin\aichat\backend;

use Hexogen\KDTree\FSKDTree;
use Hexogen\KDTree\FSTreePersister;
use Hexogen\KDTree\Item;
use Hexogen\KDTree\ItemFactory;
use Hexogen\KDTree\ItemList;
use Hexogen\KDTree\KDTree;
use Hexogen\KDTree\NearestSearch;
use Hexogen\KDTree\Point;

/**
 * Implements storage on top of a K-D Tree. Chunks are serialized as JSON to the file system.
 */
class KDTreeStorage extends AbstractStorage
{

    const INDEX_NAME = 'aichat';
    const INDEX_FILE = 'index.bin';

    /** @var ItemList holds items during storage creation */
    protected $itemList;

    /** @inheritdoc */
    public function getChunk($chunkID)
    {
        $chunkfile = $this->getChunkFilePath($chunkID);
        if (!file_exists($chunkfile)) return null;

        return Chunk::fromJSON(io_readFile($chunkfile, false));
    }

    /** @inheritdoc */
    public function startCreation($dimension, $clear = false)
    {
        $this->itemList = new ItemList($dimension);
    }

    /** @inheritdoc */
    public function reusePageChunks($page, $firstChunkID)
    {
        for ($i = 0; $i < 100; $i++) {
            $chunk = $this->getChunk($firstChunkID + $i);
            if (!$chunk) break;
            $item = new Item($chunk->getId(), $chunk['embedding']);
            $this->itemList->addItem($item);
        }
    }

    /** @inheritdoc */
    public function deletePageChunks($page, $firstChunkID)
    {
        for ($i = 0; $i < 100; $i++) {
            $chunkPath = $this->getChunkFilePath($firstChunkID + $i);
            if (!file_exists($chunkPath)) break;
            unlink($chunkPath);
        }
    }

    /** @inheritdoc */
    public function addPageChunks($chunks)
    {
        foreach ($chunks as $chunk) {
            $item = new Item($chunk->getId(), $chunk->getEmbedding());
            $this->itemList->addItem($item);

            $chunkfile = $this->getChunkFilePath($chunk->getId());
            io_saveFile($chunkfile, json_encode($chunk));
        }
    }

    /** @inheritdoc */
    public function finalizeCreation()
    {
        $tree = new KDTree($this->itemList);
        $persister = new FSTreePersister($this->getStorageDir());
        $persister->convert($tree, self::INDEX_FILE);
        $this->itemList = null; // garbage collect
    }

    /** @inheritdoc */
    public function getSimilarChunks($vector, $limit = 4) {
        $fsTree = $this->getTree();
        $fsSearcher = new NearestSearch($fsTree);
        $items = $fsSearcher->search(new Point($vector), $limit * 2); // we get twice as many as needed

        $result = [];
        foreach ($items as $item) {
            $result[] = $this->getChunk($item->getId());
        }
        return $result;
    }

    /** @inheritdoc */
    public function statistics()
    {
        $file = $this->getStorageDir() . self::INDEX_FILE;
        $fsTree = $this->getTree();
        return [
            'type' => 'KDTree',
            'chunks' => $fsTree->getItemCount(),
            'dimension' => $fsTree->getDimensionCount(),
            'file size' => filesize_h(filesize($file)),
            'last modified' => dformat(filemtime($file)),
        ];
    }

    /**
     * Access to the stored KD Tree
     *
     * @return FSKDTree
     */
    protected function getTree()
    {
        $file = $this->getStorageDir() . self::INDEX_FILE;
        return new FSKDTree($file, new ItemFactory());
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
