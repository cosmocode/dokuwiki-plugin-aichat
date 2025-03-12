<?php

namespace dokuwiki\plugin\aichat\Model\Groq;

use dokuwiki\plugin\aichat\Model\AbstractModel;
use dokuwiki\plugin\aichat\Model\ChatInterface;

class ChatModel extends AbstractModel implements ChatInterface
{
    /** @inheritdoc */
    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);

        if (empty($config['groq_apikey'])) {
            throw new \Exception('Groq API key not configured', 3001);
        }

        $this->http->headers['Authorization'] = 'Bearer ' . $config['groq_apikey'];
    }

    /** @inheritdoc */
    public function getAnswer(array $messages): string
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
     * Send a request to the API
     *
     * @param string $endpoint
     * @param array $data Payload to send
     * @return array API response
     * @throws \Exception
     */
    protected function request($endpoint, $data)
    {
        $url = 'https://api.groq.com/openai/v1/' . $endpoint;
        return $this->sendAPIRequest('POST', $url, $data);
    }

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        if (isset($response['usage'])) {
            $this->inputTokensUsed += $response['usage']['prompt_tokens'];
            $this->outputTokensUsed += $response['usage']['completion_tokens'] ?? 0;
        }

        if (isset($response['error'])) {
            throw new \Exception('Groq API error: ' . $response['error']['message'], 3002);
        }

        return $response;
    }
}
