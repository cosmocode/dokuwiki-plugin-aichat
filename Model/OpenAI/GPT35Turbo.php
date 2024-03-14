<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\plugin\aichat\Model\AbstractChatModel;

/**
 * Basic OpenAI Client using the standard GPT-3.5-turbo model
 *
 * Additional OpenAI models just overwrite the $setup array
 */
class GPT35Turbo extends AbstractChatModel
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
        return 'gpt-3.5-turbo';
    }

    /** @inheritdoc */
    public function get1kTokenPrice()
    {
        return 0.0015;
    }

    /** @inheritdoc */
    public function getMaxContextTokenLength()
    {
        return 3500;
    }

    /** @inheritdoc */
    public function getMaxRephrasingTokenLength()
    {
        return 3500;
    }

    /** @inheritdoc */
    public function getMaxEmbeddingTokenLength()
    {
        return 1000;
    }


    /** @inheritdoc */
    public function getAnswer($messages)
    {
        return $this->getChatCompletion($messages);
    }

    /** @inheritdoc */
    public function getRephrasedQuestion($messages)
    {
        return $this->getChatCompletion($messages);
    }

    /**
     * @internal for checking available models
     */
    public function listUpstreamModels()
    {
        $url = 'https://api.openai.com/v1/models';
        $result = $this->client->getHTTPClient()->http->get($url);
        return $result;
    }

    /**
     * Send data to the chat endpoint
     *
     * @param array $messages Messages in OpenAI format (with role and content)
     * @return string The answer
     * @throws \Exception
     */
    protected function getChatCompletion($messages)
    {
        $data = [
            'messages' => $messages,
            'model' => $this->getModelName(),
            'max_tokens' => null,
            'stream' => false,
            'n' => 1, // number of completions
            'temperature' => 0.0,
        ];
        $response = $this->request('chat/completions', $data);
        return $response['choices'][0]['message']['content'];
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
        $this->costEstimate += $stats['tokens'] * $this->get1kTokenPrice() * (int)($this->get1kTokenPrice() * 10000);
        $this->timeUsed += $stats['time'];
        $this->requestsMade += $stats['requests'];

        return $result;
    }
}
