<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\HTTP\DokuHTTPClient;

class Client
{
    /** @var int How often to retry a request if it fails */
    public const MAX_RETRIES = 3;

    /** @var DokuHTTPClient */
    protected $http;

    /** @var int start time of the current request chain (may be multiple when retries needed) */
    protected $requestStart = 0;

    /** @var int[] Statistics on the last request chain */
    protected $stats = [
        'tokens' => 0,
        'cost' => 0,
        'time' => 0,
        'requests' => 0,
    ];

    /**
     * Intitialize the OpenAI client
     *
     * @param string $openAIKey
     * @param string $openAIOrg
     */
    public function __construct($openAIKey, $openAIOrg = '')
    {
        $this->http = new DokuHTTPClient();
        $this->http->timeout = 60;
        $this->http->headers['Authorization'] = 'Bearer ' . $openAIKey;
        if ($openAIOrg) {
            $this->http->headers['OpenAI-Organization'] = $openAIOrg;
        }
        $this->http->headers['Content-Type'] = 'application/json';
    }

    /**
     * Send a request to the OpenAI API
     *
     * @param string $endpoint
     * @param array $data Payload to send
     * @param int $retry How often this request has been retried
     * @return array API response
     * @throws \JsonException
     */
    public function request($endpoint, $data, $retry = 0)
    {
        if ($retry === 0) {
            $this->resetStats();
        } else {
            sleep($retry); // wait a bit between retries
        }
        $this->stats['requests']++;

        $url = 'https://api.openai.com/v1/' . $endpoint;

        /** @noinspection PhpParamsInspection */
        $this->http->post($url, json_encode($data, JSON_THROW_ON_ERROR));
        $response = $this->http->resp_body;
        if ($response === false || $this->http->error) {
            if ($retry < self::MAX_RETRIES) {
                return $this->request($endpoint, $data, $retry + 1);
            }

            $this->requestStart = 0;
            throw new \Exception('OpenAI API returned no response. ' . $this->http->error);
        }

        $result = json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR);
        if (!$result) {
            $this->requestStart = 0;
            throw new \Exception('OpenAI API returned invalid JSON: ' . $response);
        }
        if (isset($result['error'])) {
            if ($retry < self::MAX_RETRIES) {
                return $this->request($endpoint, $data, $retry + 1);
            }
            $this->requestStart = 0;
            throw new \Exception('OpenAI API returned error: ' . $result['error']['message']);
        }

        // update usage statistics
        if (isset($result['usage'])) $this->stats['tokens'] += $result['usage']['total_tokens'];
        $this->stats['time'] = microtime(true) - $this->requestStart;

        return $result;
    }

    /**
     * Get the usage statistics for the last request chain
     *
     * @return int[]
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Access the DokuHTTPClient directly
     *
     * @return DokuHTTPClient
     */
    public function getHTTPClient()
    {
        return $this->http;
    }

    /**
     * Reset the statistics for a new request
     * @return void
     */
    protected function resetStats()
    {
        $this->requestStart = microtime(true);
        $this->stats = [
            'tokens' => 0,
            'time' => 0,
            'requests' => 0,
        ];
    }
}
