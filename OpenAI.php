<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\http\DokuHTTPClient;

class OpenAI
{
    const EMBEDDING_MODEL = 'text-embedding-ada-002';
    const CHAT_MODEL = 'gpt-3.5-turbo';

    protected $http;

    /**
     * Initialize the OpenAI API
     *
     * @param string $openAIKey
     * @param string $openAIOrg
     */
    public function __construct($openAIKey, $openAIOrg = '')
    {
        $this->http = new DokuHTTPClient();
        $this->http->timeout = 60;
        $this->http->headers['Authorization'] = 'Bearer ' . $openAIKey;
        if ($openAIOrg) {
            $this->http->headers['OpenAI-Organization'] = $openAIOrg;
        }
        $this->http->headers['Content-Type'] = 'application/json';
    }

    /**
     * Get the embedding vectors for a given text
     *
     * @param string $text
     * @return float[]
     * @throws \Exception
     */
    public function getEmbedding($text)
    {
        $data = [
            'model' => self::EMBEDDING_MODEL,
            'input' => [$text],
        ];
        $response = $this->request('embeddings', $data);

        return $response['data'][0]['embedding'];
    }

    /**
     * Send data to the chat endpoint
     *
     * @param array $messages Messages in OpenAI format (with role and content)
     * @return string The answer
     * @throws \Exception
     */
    public function getChatAnswer($messages) {
        $data = [
            'messages' => $messages,
            'model' => self::CHAT_MODEL,
            'max_tokens' => null,
            'stream' => false,
            'n' => 1, // number of completions
            'temperature' => 0.0,
        ];
        $response = $this->request('chat/completions', $data);
        return $response['choices'][0]['message']['content'];
    }

    /**
     * Send a request to the OpenAI API
     *
     * @param string $endpoint
     * @param array $data Payload to send
     * @return array API response
     * @todo add retry, when openAI responds with model overload
     * @throws \Exception
     */
    protected function request($endpoint, $data)
    {
        $url = 'https://api.openai.com/v1/' . $endpoint;

        /** @noinspection PhpParamsInspection */
        $this->http->post($url, json_encode($data));
        $response = $this->http->resp_body;
        if ($response === false || $this->http->error) {
            throw new \Exception('OpenAI API returned no response. ' . $this->http->error);
        }

        $data = json_decode($response, true);
        if (!$data) {
            throw new \Exception('OpenAI API returned invalid JSON: '.$response);
        }
        if (isset($data['error'])) {
            throw new \Exception('OpenAI API returned error: ' . $data['error']['message']);
        }
        return $data;
    }


}
