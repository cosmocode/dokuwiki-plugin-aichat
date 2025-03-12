<?php

namespace dokuwiki\plugin\aichat\Model\VoyageAI;

use dokuwiki\plugin\aichat\Model\AbstractModel;
use dokuwiki\plugin\aichat\Model\EmbeddingInterface;

class EmbeddingModel extends AbstractModel implements EmbeddingInterface
{
    /** @inheritdoc */
    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);

        if (empty($config['voyageai_apikey'])) {
            throw new \Exception('Voyage AI API key not configured', 3001);
        }

        $this->http->headers['Authorization'] = 'Bearer ' . $config['voyageai_apikey'];
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
     * Send a request to the Voyage API
     *
     * @param string $endpoint
     * @param array $data Payload to send
     * @return array API response
     * @throws \Exception
     */
    protected function request($endpoint, $data)
    {
        $url = 'https://api.voyageai.com/v1/' . $endpoint;
        return $this->sendAPIRequest('POST', $url, $data);
    }

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        if (isset($response['usage'])) {
            $this->inputTokensUsed += $response['usage']['total_tokens'];
        }

        if (isset($response['error'])) {
            throw new \Exception('OpenAI API error: ' . $response['error']['message'], 3002);
        }

        return $response;
    }
}
