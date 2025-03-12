<?php

namespace dokuwiki\plugin\aichat\Model\Ollama;

use dokuwiki\plugin\aichat\Model\AbstractModel;

/**
 * Abstract Ollama Model
 *
 * This class provides a basic interface to the Ollama API
 */
abstract class AbstractOllama extends AbstractModel
{
    protected $apiurl = 'http://localhost:11434/api/';

    /** @inheritdoc */
    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);
        $this->apiurl = rtrim($config['ollama_baseurl'] ?? '', '/');
        if ($this->apiurl === '') {
            throw new \Exception('Ollama base URL not configured', 3001);
        }
    }

    /** @inheritdoc */
    function loadUnknownModelInfo(): array
    {
        $info = parent::loadUnknownModelInfo();

        $url = $this->apiurl . 'show';

        $result = $this->sendAPIRequest('POST', $url, ['model' => $this->modelName]);
        foreach($result['model_info'] as $key => $value) {
            if(str_ends_with($key, '.context_length')) {
                $info['inputTokens'] = $value;
            }
            if(str_ends_with($key, '.embedding_length')) {
                $info['dimensions'] = $value;
            }

        }

        return $info;
    }

    /**
     * Send a request to the Ollama API
     *
     * @param string $endpoint
     * @param array $data Payload to send
     * @return array API response
     * @throws \Exception
     */
    protected function request($endpoint, $data)
    {
        $url = $this->apiurl . '/' . ltrim($endpoint, '/');
        return $this->sendAPIRequest('POST', $url, $data);
    }

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        if (isset($response['eval_count'])) {
            $this->inputTokensUsed += $response['eval_count'];
        }

        if (isset($response['error'])) {
            $error = is_array($response['error']) ? $response['error']['message'] : $response['error'];
            throw new \Exception('Ollama API error: ' . $error, 3002);
        }

        return $response;
    }
}
