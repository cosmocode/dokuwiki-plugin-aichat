<?php

namespace dokuwiki\plugin\aichat\Model\Gemini;

use dokuwiki\plugin\aichat\Model\AbstractModel;

abstract class AbstractGeminiModel extends AbstractModel
{

    /** @var string Gemini API key */
    protected $apikey;

    /** @inheritdoc */
    public function __construct(string $name, array $config)
    {
        if (empty($config['gemini_apikey'])) {
            throw new \Exception('Gemini API key not configured', 3001);
        }

        $this->apikey = $config['gemini_apikey'];

        parent::__construct($name, $config);
    }

    /** @inheritdoc */
    function loadUnknownModelInfo(): array
    {
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s?key=%s',
            $this->modelName,
            $this->apikey
        );
        $result = $this->sendAPIRequest('GET', $url, '');
        if(!$result) {
            throw new \Exception('Failed to load model info for '.$this->modelFullName, 3003);
        }

        $info = parent::loadUnknownModelInfo();
        $info['description'] = $result['description'];
        $info['inputTokens'] = $result['inputTokenLimit'];
        $info['outputTokens'] = $result['outputTokenLimit'];
        return $info;
    }

    /**
     * Send a request to the Gemini API
     *
     * @param string $endpoint
     * @param array $data Payload to send
     * @return array API response
     * @throws \Exception
     */
    protected function request($model, $endpoint, $data)
    {
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:%s?key=%s',
            $model,
            $endpoint,
            $this->apikey
        );

        return $this->sendAPIRequest('POST', $url, $data);
    }

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        if (isset($response['usageMetadata'])) {
            $this->inputTokensUsed += $response['usageMetadata']['promptTokenCount'];
            $this->outputTokensUsed += $response['usageMetadata']['candidatesTokenCount'] ?? 0;
        }

        if (isset($response['error'])) {
            throw new \Exception('Gemini API error: ' . $response['error']['message'], 3002);
        }

        return $response;
    }


}
