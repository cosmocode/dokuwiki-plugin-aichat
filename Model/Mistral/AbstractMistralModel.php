<?php

namespace dokuwiki\plugin\aichat\Model\Mistral;

use dokuwiki\plugin\aichat\Model\AbstractModel;

/**
 * Abstract OpenAI Model
 *
 * This class provides a basic interface to the OpenAI API
 */
abstract class AbstractMistralModel extends AbstractModel
{
    /** @inheritdoc */
    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);
        if (empty($config['mistral_apikey'])) {
            throw new \Exception('Mistral API key not configured', 3001);
        }
        $this->http->headers['Authorization'] = 'Bearer ' . $config['mistral_apikey'];
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
        $url = 'https://api.mistral.ai/v1/' . $endpoint;
        return $this->sendAPIRequest('POST', $url, $data);
    }

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        if (isset($response['usage'])) {
            $this->inputTokensUsed += $response['usage']['prompt_tokens'];
            $this->outputTokensUsed += $response['usage']['completion_tokens'] ?? 0;
        }

        if (isset($response['object']) && $response['object'] === 'error') {
            throw new \Exception('Mistral API error: ' . $response['message'], 3002);
        }

        return $response;
    }

    /**
     * @internal for checking available models
     */
    public function listUpstreamModels()
    {
        $url = 'https://api.openai.com/v1/models';
        return $this->http->get($url);
    }
}
