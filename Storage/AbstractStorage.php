<?php

namespace dokuwiki\plugin\aichat\Storage;

use dokuwiki\plugin\aichat\Chunk;

/**
 * Defines a vector storage for page chunks and their embeddings
 *
 * Please not that chunkIDs are created outside of the storage. They reference the Page's ID in
 * DokuWiki's fulltext index. ChunkIDs count from the page's id*100 upwards. Eg. Page 12 will have
 * chunks 1200, 1201, 1202, ...
 */
abstract class AbstractStorage
{

    /**
     * Get the chunk with the given ID
     *
     * @param int $chunkID
     * @return Chunk|null
     */
    abstract public function getChunk($chunkID);

    /**
     * Called when the storage is about to be (re)built
     *
     * Storages may need to open a transaction or prepare other things here.
     *
     * @param bool $clear Should any existing data been thrown away?
     * @return void
     */
    abstract public function startCreation($clear = false);

    /**
     * Called when the storage is (re)built and the existing chunks should be reused
     *
     * Storages that can be updated, may simply do nothing here
     *
     * @param string $page The page the chunks belong to
     * @param int $firstChunkID The ID of the first chunk to reuse
     * @return void
     */
    abstract public function reusePageChunks($page, $firstChunkID);

    /**
     * Delete all chunks associated with the given page
     *
     * @param string $page The page the chunks belong to
     * @param int $firstChunkID The ID of the first chunk to reuse (may not exist)
     * @return void
     */
    abstract public function deletePageChunks($page, $firstChunkID);

    /**
     * Add the given new Chunks to the storage
     *
     * @param Chunk[] $chunks
     * @return void
     */
    abstract public function addPageChunks($chunks);

    /**
     * All chunks have been added, finalize the storage
     *
     * This is where transactions may be committed and or memory structures be written to disk.
     *
     * @return void
     */
    abstract public function finalizeCreation();

    /**
     * Get the chunks most similar to the given vector, using a nearest neighbor search
     *
     * The returned chunks should be sorted by similarity, most similar first.
     *
     * If possible in an efficient way, only chunks readable by the current user should be returned (ACL check).
     * If not, the storage should return twice the $limit of chunks and the caller will filter out the readable ones.
     *
     * @param float[] $vector The vector to compare to
     * @param int $limit The number of results to return, see note above
     * @return Chunk[]
     */
    abstract public function getSimilarChunks($vector, $limit = 4);

    /**
     * Get information about the storage
     *
     * Each storage can decide on it's own what to return here as key value pairs. Keys should be self explanatory.
     *
     * @return string[]
     */
    abstract public function statistics();
}
