<?php


namespace dokuwiki\plugin\aichat\Storage;

use dokuwiki\plugin\aichat\Chunk;
use dokuwiki\plugin\sqlite\SQLiteDB;

/**
 * Implements the storage backend using a SQLite database
 *
 * Note: all embeddings are stored and returned as normalized vectors
 */
class SQLiteStorage extends AbstractStorage
{
    /** @var float minimum similarity to consider a chunk a match */
    const SIMILARITY_THRESHOLD = 0.75;

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
        $this->db->getPdo()->sqliteCreateFunction('COSIM', [$this, 'sqliteCosineSimilarityCallback'], 2);
    }

    /** @inheritdoc */
    public function getChunk($chunkID)
    {
        $record = $this->db->queryRecord('SELECT * FROM embeddings WHERE id = ?', [$chunkID]);
        if (!$record) return null;

        return new Chunk(
            $record['page'],
            $record['id'],
            $record['chunk'],
            json_decode($record['embedding'], true),
            $record['created']
        );
    }

    /** @inheritdoc */
    public function startCreation($clear = false)
    {
        if ($clear) {
            /** @noinspection SqlWithoutWhere */
            $this->db->exec('DELETE FROM embeddings');
        }
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

    /** @inheritdoc */
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

    /** @inheritdoc */
    public function finalizeCreation()
    {
        $this->db->exec('VACUUM');
    }

    /** @inheritdoc */
    public function getPageChunks($page, $firstChunkID)
    {
        $result = $this->db->queryAll(
            'SELECT * FROM embeddings WHERE page = ?',
            [$page]
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
    public function getSimilarChunks($vector, $limit = 4)
    {
        $result = $this->db->queryAll(
            'SELECT *, COSIM(?, embedding) AS similarity
               FROM embeddings
              WHERE GETACCESSLEVEL(page) > 0
                AND similarity > CAST(? AS FLOAT)
           ORDER BY similarity DESC
              LIMIT ?',
            [json_encode($vector), self::SIMILARITY_THRESHOLD, $limit]
        );
        $chunks = [];
        foreach ($result as $record) {
            $chunks[] = new Chunk(
                $record['page'],
                $record['id'],
                $record['chunk'],
                json_decode($record['embedding'], true),
                $record['created'],
                $record['similarity']
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
            'storage type' => 'SQLite',
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
     * Actually just calculating the dot product of the two vectors, since they are normalized
     *
     * @param float[] $queryVector The normalized vector of the search phrase
     * @param float[] $embedding The normalized vector of the chunk
     * @return float
     */
    protected function cosineSimilarity($queryVector, $embedding)
    {
        $dotProduct = 0;
        foreach ($queryVector as $key => $value) {
            $dotProduct += $value * $embedding[$key];
        }
        return $dotProduct;
    }
}
