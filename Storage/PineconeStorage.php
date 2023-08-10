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

    /**
     * PineconeStorage constructor.
     */
    public function __construct()
    {
        $helper = plugin_load('helper', 'aichat');

        $this->baseurl = $helper->getConf('pinecone_baseurl');

        $this->http = new DokuHTTPClient();
        $this->http->headers['Api-Key'] = $helper->getConf('pinecone_apikey');
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
    protected function runQuery($endpoint, $data, $method = 'POST')
    {
        $url = $this->baseurl . $endpoint;

        if (is_array($data) && !count($data)) {
            $json = '{}';
        } else {
            $json = json_encode($data);
        }

        $this->http->sendRequest($url, $json, $method);
        $response = $this->http->resp_body;
        if ($response === false) {
            throw new \Exception('Pinecone API returned no response. ' . $this->http->error);
        }

        $result = json_decode($response, true);
        if ($result === null) {
            throw new \Exception('Pinecone API returned invalid JSON. ' . $response);
        }

        if (isset($result['message'])) {
            throw new \Exception('Pinecone API returned error. ' . $result['message']);
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
            } catch (\Exception $e) {
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
        $ids = array_map(function ($id) {
            return (string)$id;
        }, $ids);
        $this->runQuery('/vectors/delete', ['ids' => $ids]);
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

    /**
     * Pinecone can't query based on metadata, so we have to get all possible chunks by ID
     *
     * @link https://community.pinecone.io/t/fetch-vectors-based-only-on-metadata-filters/2140
     * @inheritdoc
     */
    public function getPageChunks($page, $firstChunkID)
    {
        $ids = range($firstChunkID, $firstChunkID + 99, 1);
        $ids = array_reduce($ids, function ($carry, $item) {
            return $carry . '&ids=' . $item;
        });

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
                $vector['metadata']['created']
            );
        }
        return $chunks;
    }

    /** @inheritdoc */
    public function getSimilarChunks($vector, $limit = 4)
    {
        $limit = $limit * 2; // we can't check ACLs, so we return more than requested

        $response = $this->runQuery(
            '/query',
            [
                'vector' => $vector,
                'topK' => (int)$limit,
                'include_metadata' => true,
                'include_values' => true,
            ]
        );
        $chunks = [];
        foreach ($response['matches'] as $vector) {
            $chunks[] = new Chunk(
                $vector['metadata']['page'],
                $vector['id'],
                $vector['metadata']['text'],
                $vector['values'],
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
