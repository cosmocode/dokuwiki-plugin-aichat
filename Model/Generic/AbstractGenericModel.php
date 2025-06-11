<?php

namespace dokuwiki\plugin\aichat\Model\Generic;

use dokuwiki\plugin\aichat\Model\AbstractModel;
use dokuwiki\plugin\aichat\Model\ChatInterface;
use dokuwiki\plugin\aichat\Model\EmbeddingInterface;
use dokuwiki\plugin\aichat\Model\ModelException;

/**
 * Abstract OpenAI-compatible Model
 *
 * This class provides a basic interface to the OpenAI API as implemented by many other providers.
 * It implements chat and embedding interfaces.
 */
abstract class AbstractGenericModel extends AbstractModel implements ChatInterface, EmbeddingInterface
{
    /** @var string The API base URL */
    protected $apiurl = '';


    /** @inheritdoc */
    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);

        if($this->apiurl === '') {
            $this->apiurl = $this->getFromConf('apiurl');
        }
        $this->apiurl = rtrim($this->apiurl, '/');
    }

    /** @inheritdoc */
    protected function getHttpClient()
    {
        $http = parent::getHttpClient();

        $apiKey = $this->getFromConf('apikey');
        $http->headers['Authorization'] = 'Bearer ' . $apiKey;
        return $http;
    }

    /**
     * Send a request to the OpenAI API
     *
     * @param string $endpoint
     * @param array $data Payload to send
     * @return array API response
     * @throws \Exception
     */
    protected function request($endpoint, $data)
    {
        $url = $this->apiurl . '/' . $endpoint;
        return $this->sendAPIRequest('POST', $url, $data);
    }

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        if (isset($response['usage'])) {
            if(isset($response['usage']['prompt_tokens'])) {
                $this->inputTokensUsed += $response['usage']['prompt_tokens'];
            } elseif ($response['usage']['total_tokens']) {
                // on embedding models, prompt_tokens is not available
                $this->inputTokensUsed += $response['usage']['total_tokens'];
            }
            $this->outputTokensUsed += $response['usage']['completion_tokens'] ?? 0;
        }

        if (isset($response['error'])) {
            throw new ModelException('API error: ' . $response['error']['message'], 3002);
        }

        return $response;
    }

    /** @inheritdoc */
    public function getAnswer(array $messages): string
    {
        $data = [
            'messages' => $messages,
            'model' => $this->getModelName(),
            'max_completion_tokens' => null,
            'stream' => false,
            'n' => 1, // number of completions
            'temperature' => 0.0
        ];

        $response = $this->request('chat/completions', $data);
        return $response['choices'][0]['message']['content'];
    }

    /** @inheritdoc */
    public function getEmbedding($text): array
    {
        $data = [
            'model' => $this->getModelName(),
            'input' => [$text],
        ];
        $response = $this->request('embeddings', $data);

        return $response['data'][0]['embedding'];
    }

    /**
     * @internal for checking available models
     */
    public function listUpstreamModels()
    {
        $http = $this->getHttpClient();
        $url = $this->apiurl . '/models';
        return $http->get($url);
    }
}
