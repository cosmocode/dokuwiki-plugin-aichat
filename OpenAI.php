<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\http\DokuHTTPClient;

/**
 * Client to communicate with the OpenAI API
 */
class OpenAI
{
    const EMBEDDING_MODEL = 'text-embedding-ada-002';
    const CHAT_MODEL = 'gpt-3.5-turbo';

    /**
     * real 1K cost multiplied by 10000 to avoid floating point issues
     */
    const PRICING = [
        self::EMBEDDING_MODEL => 4, // $0.0004 per 1k token
        self::CHAT_MODEL => 20, // $0.002 per 1k token
    ];


    /** @var int How often to retry a request if it fails */
    const MAX_RETRIES = 3;

    /** @var DokuHTTPClient */
    protected $http;

    /** @var int total tokens used by this instance */
    protected $tokensUsed = 0;
    /** @var int total cost used by this instance (multiplied by 1000*10000) */
    protected $costEstimate = 0;

    /** @var int total time spent in requests by this instance */
    protected $timeUsed = 0;

    /** @var int total number of requests made by this instance */
    protected $requestsMade = 0;

    /** @var int start of the current request chain (may be multiple when retries needed) */
    protected $requestStart = 0;

    /**
     * Initialize the OpenAI API
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
     * Get the embedding vectors for a given text
     *
     * @param string $text
     * @return float[]
     * @throws \Exception
     */
    public function getEmbedding($text)
    {
        $data = [
            'model' => self::EMBEDDING_MODEL,
            'input' => [$text],
        ];
        $response = $this->request('embeddings', $data);

        return $response['data'][0]['embedding'];
    }

    /**
     * Send data to the chat endpoint
     *
     * @param array $messages Messages in OpenAI format (with role and content)
     * @return string The answer
     * @throws \Exception
     */
    public function getChatAnswer($messages)
    {
        $data = [
            'messages' => $messages,
            'model' => self::CHAT_MODEL,
            'max_tokens' => null,
            'stream' => false,
            'n' => 1, // number of completions
            'temperature' => 0.0,
        ];
        $response = $this->request('chat/completions', $data);
        return $response['choices'][0]['message']['content'];
    }

    /**
     * Send a request to the OpenAI API
     *
     * @param string $endpoint
     * @param array $data Payload to send
     * @return array API response
     * @throws \Exception
     */
    protected function request($endpoint, $data, $retry = 0)
    {
        if ($retry) sleep($retry); // wait a bit between retries
        if (!$this->requestStart) $this->requestStart = microtime(true);
        $this->requestsMade++;

        $url = 'https://api.openai.com/v1/' . $endpoint;

        /** @noinspection PhpParamsInspection */
        $this->http->post($url, json_encode($data));
        $response = $this->http->resp_body;
        if ($response === false || $this->http->error) {
            if ($retry < self::MAX_RETRIES) {
                return $this->request($endpoint, $data, $retry + 1);
            }

            $this->requestStart = 0;
            throw new \Exception('OpenAI API returned no response. ' . $this->http->error);
        }

        $result = json_decode($response, true);
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
        $this->tokensUsed += $result['usage']['total_tokens'];
        $this->costEstimate += $result['usage']['total_tokens'] * self::PRICING[$data['model']];
        $this->timeUsed += microtime(true) - $this->requestStart;
        $this->requestStart = 0;

        return $result;
    }

    /**
     * Reset the usage statistics
     *
     * Usually not needed when only handling one operation per request, but useful in CLI
     */
    public function resetUsageStats()
    {
        $this->tokensUsed = 0;
        $this->costEstimate = 0;
        $this->timeUsed = 0;
        $this->requestsMade = 0;
    }

    /**
     * Get the usage statistics for this instance
     *
     * @return string[]
     */
    public function getUsageStats()
    {
        return [
            'tokens' => $this->tokensUsed,
            'cost' => round($this->costEstimate / 1000 / 10000, 4),
            'time' => round($this->timeUsed, 2),
            'requests' => $this->requestsMade,
        ];
    }

}
