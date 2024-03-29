<?php

namespace dokuwiki\plugin\aichat\Storage;

use dokuwiki\HTTP\DokuHTTPClient;
use dokuwiki\plugin\aichat\Chunk;

/**
 * Implements the storage backend using a Chroma DB in server mode
 */
class QdrantStorage extends AbstractStorage
{
    /** @var string URL to the qdrant server instance */
    protected $baseurl;

    /** @var DokuHTTPClient http client */
    protected $http;

    protected $collection = '';
    protected $collectionName = '';


    /**
     * QdrantStorage constructor.
     */
    public function __construct()
    {
        $helper = plugin_load('helper', 'aichat');

        $this->baseurl = $helper->getConf('qdrant_baseurl');
        $this->collectionName = $helper->getConf('qdrant_collection');

        $this->http = new DokuHTTPClient();
        $this->http->headers['Content-Type'] = 'application/json';
        $this->http->headers['Accept'] = 'application/json';
        $this->http->keep_alive = false;
        $this->http->timeout = 30;

        if ($helper->getConf('qdrant_apikey')) {
            $this->http->headers['api-key'] = $helper->getConf('qdrant_apikey');
        }
    }

    /**
     * Execute a query against the Qdrant API
     *
     * @param string $endpoint API endpoint, will be added to the base URL
     * @param mixed $data The data to send, will be JSON encoded
     * @param string $method POST|GET|PUT etc
     * @return mixed
     * @throws \Exception
     */
    protected function runQuery($endpoint, mixed $data, $method = 'POST')
    {
        $endpoint = trim($endpoint, '/');
        $url = $this->baseurl . '/' . $endpoint . '?wait=true';

        if ($data === []) {
            $json = '{}';
        } else {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
        }

        $this->http->sendRequest($url, $json, $method);
        $response = $this->http->resp_body;

        if (!$response) {
            throw new \Exception(
                'Qdrant API returned no response. ' . $this->http->error . ' Status: ' . $this->http->status
            );
        }

        try {
            $result = json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception) {
            throw new \Exception('Qdrant API returned invalid JSON. ' . $response);
        }

        if ((int)$this->http->status !== 200) {
            $error = $result['status']['error'] ?? $this->http->error;
            throw new \Exception('Qdrant API returned error. ' . $error);
        }

        return $result['result'] ?? $result;
    }

    /**
     * Get the name of the collection to use
     *
     * Initializes the collection if it doesn't exist yet
     *
     * @return string
     * @throws \Exception
     */
    public function getCollection()
    {
        if ($this->collection) return $this->collection;

        try {
            $this->runQuery('/collections/' . $this->collectionName, '', 'GET');
            $this->collection = $this->collectionName;
            return $this->collection; // collection exists
        } catch (\Exception) {
            // collection seems not to exist
        }

        $data = [
            'vectors' => [
                'size' => 1536, // FIXME should not be hardcoded
                'distance' => 'Cosine',
            ]
        ];

        // create the collection
        $this->runQuery('/collections/' . $this->collectionName, $data, 'PUT');
        $this->collection = $this->collectionName;

        return $this->collection;
    }

    /** @inheritdoc */
    public function startCreation($clear = false)
    {
        if (!$clear) return;

        // if a collection exists, delete it
        $collection = $this->getCollection();
        if ($collection) {
            $this->runQuery('/collections/' . $collection, '', 'DELETE');
            $this->collection = '';
        }
    }

    /** @inheritdoc */
    public function getChunk($chunkID)
    {
        try {
            $data = $this->runQuery(
                '/collections/' . $this->getCollection() . '/points/' . $chunkID,
                '',
                'GET'
            );
        } catch (\Exception) {
            // no such point
            return null;
        }

        return new Chunk(
            $data['payload']['page'],
            (int)$data['id'],
            $data['payload']['text'],
            $data['vector'],
            $data['payload']['language'] ?? '',
            (int)$data['payload']['created']
        );
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

        $this->runQuery(
            '/collections/' . $this->getCollection() . '/points/delete',
            [
                'points' => $ids
            ],
            'POST'
        );
    }

    /** @inheritdoc */
    public function addPageChunks($chunks)
    {
        $points = [];
        foreach ($chunks as $chunk) {
            $points[] = [
                'id' => $chunk->getId(),
                'vector' => $chunk->getEmbedding(),
                'payload' => [
                    'page' => $chunk->getPage(),
                    'text' => $chunk->getText(),
                    'created' => $chunk->getCreated(),
                    'language' => $chunk->getLanguage()
                ]
            ];
        }

        $this->runQuery(
            '/collections/' . $this->getCollection() . '/points',
            [
                'points' => $points
            ],
            'PUT'
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

        $data = $this->runQuery(
            '/collections/' . $this->getCollection() . '/points',
            [
                'ids' => $ids,
                'with_payload' => true,
                'with_vector' => true,
            ],
            'POST'
        );

        if (!$data) return [];

        $chunks = [];
        foreach ($data as $point) {
            $chunks[] = new Chunk(
                $point['payload']['page'],
                (int)$point['id'],
                $point['payload']['text'],
                $point['vector'],
                $point['payload']['language'] ?? '',
                (int)$point['payload']['created']
            );
        }
        return $chunks;
    }

    /** @inheritdoc */
    public function getSimilarChunks($vector, $lang = '', $limit = 4)
    {
        $limit *= 2; // we can't check ACLs, so we return more than requested

        if ($lang) {
            $filter = [
                'must' => [
                    [
                        'key' => 'language',
                        'match' => [
                            'value' => $lang
                        ],
                    ]
                ]
            ];
        } else {
            $filter = null;
        }

        $data = $this->runQuery(
            '/collections/' . $this->getCollection() . '/points/search',
            [
                'vector' => $vector,
                'limit' => (int)$limit,
                'filter' => $filter,
                'with_payload' => true,
                'with_vector' => true,
            ]
        );

        $chunks = [];
        foreach ($data as $point) {
            $chunks[] = new Chunk(
                $point['payload']['page'],
                (int)$point['id'],
                $point['payload']['text'],
                $point['vector'],
                $point['payload']['language'] ?? '',
                (int)$point['payload']['created'],
                $point['score']
            );
        }
        return $chunks;
    }

    /** @inheritdoc */
    public function statistics()
    {

        $info = $this->runQuery('/collections/' . $this->getCollection(), '', 'GET');
        $telemetry = $this->runQuery('/telemetry', '', 'GET');

        return [
            'qdrant_version' => $telemetry['app']['version'],
            'vector_config' => $info['config']['params']['vectors'],
            'chunks' => $info['vectors_count'],
            'segments' => $info['segments_count'],
            'status' => $info['status'],
        ];
    }
}
