<?php


namespace dokuwiki\plugin\aichat\backend;

use dokuwiki\plugin\sqlite\SQLiteDB;

/**
 * Implements the storage backend using a SQLite database
 */
class SQLiteStorage extends AbstractStorage
{
    /** @var SQLiteDB */
    protected $db;

    /**
     * Initializes the database connection and registers our custom function
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->db = new SQLiteDB('aichat', DOKU_PLUGIN . 'aichat/db/');
        $this->db->getDb()->sqliteCreateFunction('COSIM', [$this, 'sqliteCosineSimilarityCallback'], 2);
    }

    /** @inheritdoc */
    public function getChunk($chunkID)
    {
        $record = $this->db->queryRecord('SELECT * FROM embeddings WHERE id = ?', [$chunkID]);
        if(!$record) return null;
        
        return new Chunk(
            $record['page'],
            $record['id'],
            $record['chunk'],
            json_decode($record['embedding'], true),
            $record['created']
        );
    }

    public function startCreation($dimension, $clear = false)
    {
        // TODO: Implement startCreation() method.
    }

    /** @inheritdoc */
    public function reusePageChunks($page, $firstChunkID)
    {
        // no-op
    }

    /** @inheritdoc */
    public function deletePageChunks($page, $firstChunkID)
    {
        $this->db->exec('DELETE FROM embeddings WHERE page = ?', [$page]);
    }

    public function addPageChunks($chunks)
    {
        foreach ($chunks as $chunk) {
            $this->db->saveRecord('embeddings', [
                'page' => $chunk->getPage(),
                'id' => $chunk->getId(),
                'chunk' => $chunk->getText(),
                'embedding' => json_encode($chunk->getEmbedding()),
                'created' => $chunk->getCreated()
            ]);
        }
    }

    public function finalizeCreation()
    {
        // TODO: Implement finalizeCreation() method.
        $this->db->exec('VACUUM');
    }

    /** @inheritdoc */
    public function getSimilarChunks($vector, $limit = 4)
    {
        // TODO: add PERMISSION check
        $result = $this->db->queryAll(
            'SELECT *, COSIM(?, embedding) as similarity FROM embeddings ORDER BY similarity DESC LIMIT ?',
            [json_encode($vector), $limit]
        );
        $chunks = [];
        foreach ($result as $record) {
            $chunks[] = new Chunk(
                $record['page'],
                $record['id'],
                $record['chunk'],
                json_decode($record['embedding'], true),
                $record['created']
            );
        }
        return $chunks;
    }

    /** @inheritdoc */
    public function statistics()
    {
        $items = $this->db->queryValue('SELECT COUNT(*) FROM embeddings');
        $size = $this->db->queryValue(
            'SELECT page_count * page_size as size FROM pragma_page_count(), pragma_page_size()'
        );
        return [
            'type' => 'SQLite',
            'chunks' => $items,
            'db size' => filesize_h($size)
        ];
    }

    /**
     * Method registered as SQLite callback to calculate the cosine similarity
     *
     * @param string $query JSON encoded vector array
     * @param string $embedding JSON encoded vector array
     * @return float
     */
    public function sqliteCosineSimilarityCallback($query, $embedding)
    {
        return (float)$this->cosineSimilarity(json_decode($query), json_decode($embedding));
    }

    /**
     * Calculate the cosine similarity between two vectors
     *
     * @param float[] $queryVector The vector of the search phrase
     * @param float[] $embedding The vector of the chunk
     * @return float
     * @link https://doku.wiki/src-cosine-similarity
     */
    protected function cosineSimilarity($queryVector, $embedding)
    {
        $dotProduct = 0;
        $queryEmbeddingLength = 0;
        $embeddingLength = 0;

        foreach ($queryVector as $key => $value) {
            $dotProduct += $value * $embedding[$key];
            $queryEmbeddingLength += $value * $value;
            $embeddingLength += $embedding[$key] * $embedding[$key];
        }

        return $dotProduct / (sqrt($queryEmbeddingLength) * sqrt($embeddingLength));
    }
}
