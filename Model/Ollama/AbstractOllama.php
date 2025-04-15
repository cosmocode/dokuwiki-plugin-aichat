<?php

namespace dokuwiki\plugin\aichat\Model\Ollama;

use dokuwiki\plugin\aichat\Model\Generic\AbstractGenericModel;

/**
 * Abstract Ollama Model
 *
 * This class provides a basic interface to the Ollama API
 */
abstract class AbstractOllama extends AbstractGenericModel
{

    /** @inheritdoc */
    function loadUnknownModelInfo(): array
    {
        $info = parent::loadUnknownModelInfo();

        $url = $this->apiurl . '/show';

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
