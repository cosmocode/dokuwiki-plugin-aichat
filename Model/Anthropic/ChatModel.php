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

        $this->http->headers['x-api-key'] = $config['anthropic_key'] ?? '';
        $this->http->headers['anthropic-version'] = '2023-06-01';
    }

    /** @inheritdoc */
    public function getAnswer(array $messages): string
    {
        // convert OpenAI Style to Anthropic style
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

        print_r($response);

        return $response['content'][0]['text'];
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
        $url = 'https://api.anthropic.com/v1/' . $endpoint;
        return $this->sendAPIRequest('POST', $url, $data);
    }

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        if (isset($response['usage'])) {
            $this->tokensUsed += $response['usage']['input_tokens'] + $response['usage']['output_tokens'];
        }

        if (isset($response['error'])) {
            throw new \Exception('Anthropic API error: ' . $response['error']['message']);
        }

        return $response;
    }
}
