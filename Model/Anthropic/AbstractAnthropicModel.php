<?php

namespace dokuwiki\plugin\aichat\Model\Anthropic;

use dokuwiki\plugin\aichat\Model\AbstractModel;

/**
 * Abstract Enthropic Model
 *
 * This class provides a basic interface to the Enthropic API
 */
abstract class AbstractAnthropicModel extends AbstractModel
{
    /** @inheritdoc */
    public function __construct($config)
    {
        parent::__construct($config);

        $this->http->headers['x-api-key'] = $config['anthropic_key'] ?? '';
        $this->http->headers['anthropic-version'] = '2023-06-01';
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
