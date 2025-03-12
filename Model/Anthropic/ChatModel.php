<?php

namespace dokuwiki\plugin\aichat\Model\Anthropic;

use dokuwiki\plugin\aichat\Model\AbstractModel;
use dokuwiki\plugin\aichat\Model\ChatInterface;

class ChatModel extends AbstractModel implements ChatInterface
{
    /** @inheritdoc */
    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);

        if (empty($config['anthropic_apikey'])) {
            throw new \Exception('Anthropic API key not configured', 3001);
        }

        $this->http->headers['x-api-key'] = $config['anthropic_apikey'];
        $this->http->headers['anthropic-version'] = '2023-06-01';
    }

    /** @inheritdoc */
    public function getAnswer(array $messages): string
    {
        // system message is separate from the messages array
        $system = '';
        $chat = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system .= $message['content'] . "\n";
            } else {
                $chat[] = $message;
            }
        }

        $data = [
            'messages' => $chat,
            'model' => $this->getModelName(),
            'max_tokens' => $this->getMaxOutputTokenLength(),
            'stream' => false,
            'temperature' => 0.0,
        ];

        if ($system) {
            $data['system'] = $system;
        }

        $response = $this->request('messages', $data);
        return $response['content'][0]['text'];
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
        $url = 'https://api.anthropic.com/v1/' . $endpoint;
        return $this->sendAPIRequest('POST', $url, $data);
    }

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        if (isset($response['usage'])) {
            $this->inputTokensUsed += $response['usage']['input_tokens'];
            $this->outputTokensUsed += $response['usage']['output_tokens'];
        }

        if (isset($response['error'])) {
            throw new \Exception('Anthropic API error: ' . $response['error']['message'], 3002);
        }

        return $response;
    }
}
