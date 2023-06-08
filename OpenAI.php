<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\http\DokuHTTPClient;

class OpenAI
{
    const EMBEDDING_MODEL = 'text-embedding-ada-002';


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

        /** @noinspection PhpParamsInspection */
        $this->http->post($url, json_encode($data));
        $response = $this->http->resp_body;
        if ($response === false) {
            throw new \Exception('OpenAI API returned no response. ' . $this->http->error);
        }

        $response = json_decode($response, true);
        if (!$response) {
            throw new \Exception('OpenAI API returned invalid JSON');
        }
        if (isset($response['error'])) {
            throw new \Exception('OpenAI API returned error: ' . $response['error']);
        }
        return $response;
    }


}
