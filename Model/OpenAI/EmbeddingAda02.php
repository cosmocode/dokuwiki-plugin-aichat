<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\plugin\aichat\Model\AbstractEmbeddingModel;

class EmbeddingAda02 extends AbstractEmbeddingModel
{
    /** @var Client */
    protected $client;

    /** @inheritdoc */
    public function __construct($authConfig)
    {
        $this->client = new Client(
            $authConfig['key'] ?? '',
            $authConfig['org'] ?? ''
        );
    }

    /** @inheritdoc */
    public function getModelName()
    {
        return 'text-embedding-ada-002';
    }

    /** @inheritdoc */
    public function get1kTokenPrice()
    {
        return 0.0001;
    }

    /** @inheritdoc */
    public function getMaxEmbeddingTokenLength()
    {
        return 8000; // really 8191
    }

    /** @inheritdoc */
    public function getDimensions()
    {
        return 1536;
    }

    /** @inheritdoc */
    public function getEmbedding($text)
    {
        $data = [
            'model' => $this->getModelName(),
            'input' => [$text],
        ];
        $response = $this->request('embeddings', $data);

        return $response['data'][0]['embedding'];
    }

    /**
     * Send a request to the OpenAI API and update usage statistics
     *
     * @param string $endpoint
     * @param array $data Payload to send
     * @return array API response
     * @throws \Exception
     */
    protected function request($endpoint, $data)
    {
        $result = $this->client->request($endpoint, $data);
        $stats = $this->client->getStats();

        $this->tokensUsed += $stats['tokens'];
        $this->costEstimate += $stats['tokens'] * (int)($this->get1kTokenPrice() * 10000);
        $this->timeUsed += $stats['time'];
        $this->requestsMade += $stats['requests'];

        return $result;
    }
}
