<?php

namespace dokuwiki\plugin\aichat\Model\Mistral;

use dokuwiki\plugin\aichat\Model\AbstractModel;
use dokuwiki\plugin\aichat\Model\Generic\AbstractGenericModel;

/**
 * Abstract OpenAI Model
 *
 * This class provides a basic interface to the OpenAI API
 */
abstract class AbstractMistralModel extends AbstractGenericModel
{
    protected $apiurl = 'https://api.mistral.ai/v1/';

    /** @inheritdoc */
    protected function parseAPIResponse($response)
    {
        if (isset($response['usage'])) {
            $this->inputTokensUsed += $response['usage']['prompt_tokens'] ?? 0;
            $this->outputTokensUsed += $response['usage']['completion_tokens'] ?? 0;
        }

        if (isset($response['object']) && $response['object'] === 'error') {
            throw new \Exception('Mistral API error: ' . $response['message'], 3002);
        }

        return $response;
    }

}
