<?php

namespace dokuwiki\plugin\aichat\Model\Reka;

use dokuwiki\plugin\aichat\Model\AbstractModel;
use dokuwiki\plugin\aichat\Model\ChatInterface;
use dokuwiki\plugin\aichat\Model\ModelException;

class ChatModel extends AbstractModel implements ChatInterface
{
    /** @inheritdoc */
    protected function getHttpClient()
    {
        $http = parent::getHttpClient();
        $http->headers['x-api-key'] = $this->getFromConf('apikey');
        return $http;
    }

    /** @inheritdoc */
    public function getAnswer(array $messages): string
    {
        $chat = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'user') {
                $chat[] = [
                    'type' => 'human',
                    'text' => $message['content'],
                ];
            } elseif ($message['role'] === 'assistant') {
                $chat[] = [
                    'type' => 'model',
                    'text' => $message['content'],
                ];
            }
            // system messages are not supported
        }

        $data = [
            'conversation_history' => $chat,
            'model_name' => $this->getModelName(),
            'temperature' => 0.0,
        ];

        $response = $this->request('chat', $data);
        return $response['text'];
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
        $url = 'https://api.reka.ai/' . $endpoint;
        return $this->sendAPIRequest('POST', $url, $data);
    }

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        $http = $this->getHttpClient();

        if (((int) $http->status) !== 200) {
            if (isset($response['detail'])) {
                throw new ModelException('Reka API error: ' . $response['detail'], 3002);
            } else {
                throw new ModelException('Reka API error: ' . $http->status . ' ' . $http->error, 3002);
            }
        }

        if (isset($response['metadata'])) {
            $this->inputTokensUsed += $response['metadata']['input_tokens'];
            $this->outputTokensUsed += $response['metadata']['generated_tokens'];
        }

        return $response;
    }
}
