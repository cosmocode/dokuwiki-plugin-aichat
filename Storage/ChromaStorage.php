<?php

namespace dokuwiki\plugin\aichat\Storage;

use dokuwiki\HTTP\DokuHTTPClient;
use dokuwiki\plugin\aichat\Chunk;

/**
 * Implements the storage backend using a Chroma DB in server mode
 */
class ChromaStorage extends AbstractStorage
{
    /** @var string URL to the chroma server instance */
    protected $baseurl;

    /** @var DokuHTTPClient http client */
    protected $http;

    protected $tenant = 'default_tenant';
    protected $database = 'default_database';
    protected $collection = '';
    protected $collectionID = '';

    /** @inheritdoc */
    public function __construct(array $config)
    {
        $this->baseurl = $config['chroma_baseurl'] ?? '';
        $this->tenant = $config['chroma_tenant'] ?? '';
        $this->database = $config['chroma_database'] ?? '';
        $this->collection = $config['chroma_collection'] ?? '';

        $this->http = new DokuHTTPClient();
        $this->http->headers['Content-Type'] = 'application/json';
        $this->http->headers['Accept'] = 'application/json';
        $this->http->keep_alive = false;
        $this->http->timeout = 30;

        if (!empty($config['chroma_apikey'])) {
            $this->http->headers['Authorization'] = 'Bearer ' . $config['chroma_apikey'];
        }
    }

    /**
     * Execute a query against the Chroma API
     *
     * @param string $endpoint API endpoint, will be added to the base URL
     * @param mixed $data The data to send, will be JSON encoded
     * @param string $method POST|GET
     * @return mixed
     * @throws \Exception
     */
    protected function runQuery($endpoint, mixed $data, $method = 'POST')
    {
        $url = $this->baseurl . '/api/v1' . $endpoint . '?tenant=' . $this->tenant . '&database=' . $this->database;

        if ($data === []) {
            $json = '{}';
        } else {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
        }

        $this->http->sendRequest($url, $json, $method);
        $response = $this->http->resp_body;

        if (!$response) {
            throw new \Exception('Chroma API returned no response. ' . $this->http->error, 4001);
        }

        try {
            $result = json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            throw new \Exception('Chroma API returned invalid JSON. ' . $response, 4003, $e);
        }

        if ((int)$this->http->status !== 200) {
            if (isset($result['detail'][0]['msg'])) {
                $error = $result['detail'][0]['msg'];
            } elseif (isset($result['detail']['msg'])) {
                $error = $result['detail']['msg'];
            } elseif (isset($result['detail']) && is_string($result['detail'])) {
                $error = $result['detail'];
            } elseif (isset($result['error'])) {
                $error = $result['error'];
            } else {
                $error = $this->http->error;
            }

            throw new \Exception('Chroma API returned error. ' . $error, 4002);
        }

        return $result;
    }

    /**
     * Get the collection ID for the configured collection
     *
     * @return string
     * @throws \Exception
     */
    protected function getCollectionID()
    {
        if ($this->collectionID) return $this->collectionID;

        $result = $this->runQuery(
            '/collections/',
            [
                'name' => $this->collection,
                'get_or_create' => true
            ]
        );
        $this->collectionID = $result['id'];
        return $this->collectionID;
    }

    /** @inheritdoc */
    public function getChunk($chunkID)
    {
        $data = $this->runQuery(
            '/collections/' . $this->getCollectionID() . '/get',
            [
                'ids' => [(string)$chunkID],
                'include' => [
                    'metadatas',
                    'documents',
                    'embeddings'
                ]
            ]
        );

        if (!$data) return null;
        if (!$data['ids']) return null;

        return new Chunk(
            $data['metadatas'][0]['page'],
            (int)$data['ids'][0],
            $data['documents'][0],
            $data['embeddings'][0],
            $data['metadatas'][0]['language'] ?? '',
            $data['metadatas'][0]['created']
        );
    }

    /** @inheritdoc */
    public function startCreation($clear = false)
    {
        if ($clear) {
            $this->runQuery('/collections/' . $this->collection, '', 'DELETE');
            $this->collectionID = '';
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
        $this->runQuery(
            '/collections/' . $this->getCollectionID() . '/delete',
            [
                'ids' => $ids
            ]
        );
    }

    /** @inheritdoc */
    public function addPageChunks($chunks)
    {
        $ids = [];
        $embeddings = [];
        $metadatas = [];
        $documents = [];

        foreach ($chunks as $chunk) {
            $ids[] = (string)$chunk->getId();
            $embeddings[] = $chunk->getEmbedding();
            $metadatas[] = [
                'page' => $chunk->getPage(),
                'created' => $chunk->getCreated(),
                'language' => $chunk->getLanguage()
            ];
            $documents[] = $chunk->getText();
        }

        $this->runQuery(
            '/collections/' . $this->getCollectionID() . '/upsert',
            [
                'ids' => $ids,
                'embeddings' => $embeddings,
                'metadatas' => $metadatas,
                'documents' => $documents
            ]
        );
    }

    /** @inheritdoc */
    public function finalizeCreation()
    {
        // no-op
    }

    /** @inheritdoc */
    public function runMaintenance()
    {
        // no-op
    }

    /** @inheritdoc */
    public function getPageChunks($page, $firstChunkID)
    {
        $ids = range($firstChunkID, $firstChunkID + 99, 1);
        $ids = array_map(static fn($id) => (string)$id, $ids);

        $data = $this->runQuery(
            '/collections/' . $this->getCollectionID() . '/get',
            [
                'ids' => $ids,
                'include' => [
                    'metadatas',
                    'documents',
                    'embeddings'
                ],
                'limit' => 100,
            ]
        );

        if (!$data) return [];
        if (!$data['ids']) return null;

        $chunks = [];
        foreach ($data['ids'] as $idx => $id) {
            $chunks[] = new Chunk(
                $data['metadatas'][$idx]['page'],
                (int)$id,
                $data['documents'][$idx],
                $data['embeddings'][$idx],
                $data['metadatas'][$idx]['language'] ?? '',
                $data['metadatas'][$idx]['created']
            );
        }
        return $chunks;
    }

    /** @inheritdoc */
    public function getSimilarChunks($vector, $lang = '', $limit = 4)
    {
        $limit *= 2; // we can't check ACLs, so we return more than requested

        if ($lang) {
            $filter = ['language' => $lang];
        } else {
            $filter = null;
        }

        $data = $this->runQuery(
            '/collections/' . $this->getCollectionID() . '/query',
            [
                'query_embeddings' => [$vector],
                'n_results' => (int)$limit,
                'where' => $filter,
                'include' => [
                    'metadatas',
                    'documents',
                    'embeddings',
                    'distances',
                ]
            ]
        );

        $chunks = [];
        foreach ($data['ids'][0] as $idx => $id) {
            $chunks[] = new Chunk(
                $data['metadatas'][0][$idx]['page'],
                (int)$id,
                $data['documents'][0][$idx],
                $data['embeddings'][0][$idx],
                $data['metadatas'][0][$idx]['language'] ?? '',
                $data['metadatas'][0][$idx]['created'],
                $data['distances'][0][$idx]
            );
        }
        return $chunks;
    }

    /** @inheritdoc */
    public function statistics()
    {
        $count = $this->runQuery('/collections/' . $this->getCollectionID() . '/count', '', 'GET');
        $version = $this->runQuery('/version', '', 'GET');

        return [
            'chroma_version' => $version,
            'collection_id' => $this->getCollectionID(),
            'chunks' => $count
        ];
    }
}
