<?php

namespace dokuwiki\plugin\aichat\Model\Groq;

use dokuwiki\plugin\aichat\Model\Generic\AbstractGenericModel;

class ChatModel extends AbstractGenericModel
{
    protected $apiurl = 'https://api.groq.com/openai/v1';

    /** @inheritdoc */
    function loadUnknownModelInfo(): array
    {
        $info = parent::loadUnknownModelInfo();

        $model = $this->sendAPIRequest('GET', $this->apiurl . '/models/' . $this->modelName, '');
        if (isset($model['context_window'])) {
            $info['inputTokens'] = $model['context_window'];
        }
        return $info;
    }
}
