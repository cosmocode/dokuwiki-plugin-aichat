<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\plugin\aichat\Model\AbstractModel;

/**
 * Abstract OpenAI Model
 *
 * This class provides a basic interface to the OpenAI API
 */
abstract class AbstractOpenAIModel extends AbstractModel
{
    /** @inheritdoc */
    public function __construct($config)
    {
        parent::__construct($config);

        $openAIKey = $config['openaikey'] ?? '';
        $openAIOrg = $config['openaiorg'] ?? '';

        $this->http->headers['Authorization'] = 'Bearer ' . $openAIKey;
        if ($openAIOrg) {
            $this->http->headers['OpenAI-Organization'] = $openAIOrg;
        }
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
        $url = 'https://api.openai.com/v1/' . $endpoint;
        return $this->sendAPIRequest('POST', $url, $data);
    }

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        if (isset($response['usage'])) {
            $this->tokensUsed += $response['usage']['total_tokens'];
        }

        if (isset($response['error'])) {
            throw new \Exception('OpenAI API error: ' . $response['error']['message']);
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
