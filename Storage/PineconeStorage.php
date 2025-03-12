<?php

namespace dokuwiki\plugin\aichat\Storage;

use dokuwiki\HTTP\DokuHTTPClient;
use dokuwiki\plugin\aichat\Chunk;

/**
 * Implements the storage backend using a Pinecone index
 */
class PineconeStorage extends AbstractStorage
{
    /** @var DokuHTTPClient preauthed client */
    protected $http;
    /** @var string full URL to the index instance */
    protected $baseurl;
    /** @var bool set to true when no chunks should be reused */
    protected $overwrite = false;

    /** @inheritdoc */
    public function __construct(array $config)
    {
        $this->baseurl = $config['pinecone_baseurl'] ?? '';

        $this->http = new DokuHTTPClient();
        $this->http->headers['Api-Key'] = $config['pinecone_apikey'];
        $this->http->headers['Content-Type'] = 'application/json';
        $this->http->headers['Accept'] = 'application/json';
    }

    /**
     * Execute a query against the Pinecone API
     *
     * @param string $endpoint API endpoint, will be added to the base URL
     * @param mixed $data The data to send, will be JSON encoded
     * @param string $method POST|GET
     * @return mixed
     * @throws \Exception
     */
    protected function runQuery($endpoint, mixed $data, $method = 'POST')
    {
        $url = $this->baseurl . $endpoint;

        if (is_array($data) && $data === []) {
            $json = '{}';
        } else {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
        }

        $this->http->sendRequest($url, $json, $method);
        $response = $this->http->resp_body;
        if ($response === false) {
            throw new \Exception('Pinecone API returned no response. ' . $this->http->error, 4001);
        }

        try {
            $result = json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \Exception('Pinecone API returned invalid JSON. ' . $response, 4003, $e);
        }

        if (isset($result['message'])) {
            throw new \Exception('Pinecone API returned error. ' . $result['message'], $result['code'] ?: 4002);
        }

        return $result;
    }

    /** @inheritdoc */
    public function getChunk($chunkID)
    {
        if ($this->overwrite) return null; // no reuse allowed

        $data = $this->runQuery(
            '/vectors/fetch?ids=' . $chunkID,
            '',
            'GET'
        );
        if (!$data) return null;
        $vector = array_shift($data['vectors']);
        if (!$vector) return null;

        return new Chunk(
            $vector['metadata']['page'],
            $chunkID,
            $vector['metadata']['text'],
            $vector['values'],
            $vector['metadata']['language'] ?? '',
            $vector['metadata']['created']
        );
    }

    /**
     * Proper clearing is not supported in the starter edition of pinecone. If clearing fails, we will simply
     * not reuse any existing vectors.
     *
     * @inheritdoc
     */
    public function startCreation($clear = false)
    {
        if ($clear) {
            try {
                $this->runQuery('/vectors/delete', ['delete_all' => 'True']);
            } catch (\Exception) {
                // delete all seems not supported -> starter edition
                $this->overwrite = true;
            }
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
        // delete all possible chunk IDs
        $ids = range($firstChunkID, $firstChunkID + 99, 1);
        $ids = array_map(static fn($id) => (string)$id, $ids);
        try {
            $this->runQuery('/vectors/delete', ['ids' => $ids]);
        } catch (\Exception $e) {
            // 5 is the code for "namespace not found" See #12
            if ($e->getCode() !== 5) throw $e;
        }
    }

    /** @inheritdoc */
    public function addPageChunks($chunks)
    {
        $vectors = [];
        foreach ($chunks as $chunk) {
            $vectors[] = [
                'id' => (string)$chunk->getId(),
                'values' => $chunk->getEmbedding(),
                'metadata' => [
                    'page' => $chunk->getPage(),
                    'created' => $chunk->getCreated(),
                    'text' => $chunk->getText(),
                ]
            ];
        }

        $this->runQuery('/vectors/upsert', ['vectors' => $vectors]);
    }

    /** @inheritdoc */
    public function finalizeCreation()
    {
        $this->overwrite = false;
    }

    /** @inheritdoc */
    public function runMaintenance()
    {
        // no-op
    }


    /**
     * Pinecone can't query based on metadata, so we have to get all possible chunks by ID
     *
     * @link https://community.pinecone.io/t/fetch-vectors-based-only-on-metadata-filters/2140
     * @inheritdoc
     */
    public function getPageChunks($page, $firstChunkID)
    {
        $ids = range($firstChunkID, $firstChunkID + 99, 1);
        $ids = array_reduce($ids, static fn($carry, $item) => $carry . '&ids=' . $item);

        $data = $this->runQuery(
            '/vectors/fetch?' . $ids,
            '',
            'GET'
        );
        if (!$data) return [];

        $chunks = [];
        foreach ($data['vectors'] as $vector) {
            $chunks[] = new Chunk(
                $vector['metadata']['page'],
                $vector['id'],
                $vector['metadata']['text'],
                $vector['values'],
                $vector['metadata']['language'] ?? '',
                $vector['metadata']['created']
            );
        }
        return $chunks;
    }

    /** @inheritdoc */
    public function getSimilarChunks($vector, $lang = '', $limit = 4)
    {
        $limit *= 2; // we can't check ACLs, so we return more than requested

        $query = [
            'vector' => $vector,
            'topK' => (int)$limit,
            'includeMetadata' => true,
            'includeValues' => true,
        ];

        if ($lang) {
            $query['filter'] = ['language' => ['$eq', $lang]];
        }

        $response = $this->runQuery('/query', $query);
        $chunks = [];
        foreach ($response['matches'] as $vector) {
            $chunks[] = new Chunk(
                $vector['metadata']['page'],
                $vector['id'],
                $vector['metadata']['text'],
                $vector['values'],
                $vector['metadata']['language'] ?? '',
                $vector['metadata']['created'],
                $vector['score']
            );
        }
        return $chunks;
    }

    /** @inheritdoc */
    public function statistics()
    {
        $data = $this->runQuery('/describe_index_stats', []);

        return [
            'storage type' => 'Pinecone',
            'chunks' => $data['totalVectorCount'],
            'fullness' => $data['indexFullness'],
        ];
    }
}
