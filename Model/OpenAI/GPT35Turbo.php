<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\http\DokuHTTPClient;
use dokuwiki\plugin\aichat\Model\AbstractModel;

/**
 * Basic OpenAI Client using the standard GPT-3.5-turbo model
 *
 * Additional OpenAI models just overwrite the $setup array
 */
class GPT35Turbo extends AbstractModel
{
    /** @var int[] real 1K cost multiplied by 10000 to avoid floating point issues, as of 2023-06-14 */
    protected static $prices = [
        'text-embedding-ada-002' => 1, // $0.0001 per 1k token
        'gpt-3.5-turbo' => 15, // $0.0015 per 1k token
        'gpt-3.5-turbo-16k' => 30, // $0.003 per 1k token
        'gpt-4' => 300, // $0.03 per 1k token
        'gpt-4-32k' => 600, // $0.06 per 1k token
    ];

    /** @var array[] The models and limits for the different use cases */
    protected static $setup = [
        'embedding' => ['text-embedding-ada-002', 1000], // chunk size
        'rephrase' => ['gpt-3.5-turbo', 3500], // rephrasing context size
        'chat' => ['gpt-3.5-turbo', 3500], // question context size
    ];

    /** @var int How often to retry a request if it fails */
    public const MAX_RETRIES = 3;

    /** @var DokuHTTPClient */
    protected $http;

    /** @var int start time of the current request chain (may be multiple when retries needed) */
    protected $requestStart = 0;

    /** @inheritdoc */
    public function __construct($authConfig)
    {
        $openAIKey = $authConfig['key'] ?? '';
        $openAIOrg = $authConfig['org'] ?? '';

        $this->http = new DokuHTTPClient();
        $this->http->timeout = 60;
        $this->http->headers['Authorization'] = 'Bearer ' . $openAIKey;
        if ($openAIOrg) {
            $this->http->headers['OpenAI-Organization'] = $openAIOrg;
        }
        $this->http->headers['Content-Type'] = 'application/json';
    }

    /** @inheritdoc */
    public function getMaxEmbeddingTokenLength()
    {
        return self::$setup['embedding'][1];
    }

    /** @inheritdoc */
    public function getMaxContextTokenLength()
    {
        return self::$setup['chat'][1];
    }

    /** @inheritdoc */
    public function getMaxRephrasingTokenLength()
    {
        return self::$setup['rephrase'][1];
    }

    /** @inheritdoc */
    public function getEmbedding($text)
    {
        $data = [
            'model' => self::$setup['embedding'][0],
            'input' => [$text],
        ];
        $response = $this->request('embeddings', $data);

        return $response['data'][0]['embedding'];
    }

    /** @inheritdoc */
    public function getAnswer($messages)
    {
        return $this->getChatCompletion($messages, self::$setup['chat'][0]);
    }

    /** @inheritdoc */
    public function getRephrasedQuestion($messages)
    {
        return $this->getChatCompletion($messages, self::$setup['rephrase'][0]);
    }

    /**
     * @internal for checking available models
     */
    public function listUpstreamModels()
    {
        $url = 'https://api.openai.com/v1/models';
        $result = $this->http->get($url);
        return $result;
    }

    /**
     * Send data to the chat endpoint
     *
     * @param array $messages Messages in OpenAI format (with role and content)
     * @param string $model The model to use, use the class constants
     * @return string The answer
     * @throws \Exception
     */
    protected function getChatCompletion($messages, $model)
    {
        $data = [
            'messages' => $messages,
            'model' => $model,
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
        if (isset($result['usage'])) {
            $price = self::$prices[$data['model']] ?? 0;
            $this->tokensUsed += $result['usage']['total_tokens'];
            $this->costEstimate += $result['usage']['total_tokens'] * $price;
        }
        $this->timeUsed += microtime(true) - $this->requestStart;
        $this->requestStart = 0;

        return $result;
    }
}
