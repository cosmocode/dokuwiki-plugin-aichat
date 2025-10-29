<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\plugin\aichat\Model\Generic\AbstractGenericModel;

/**
 * Abstract OpenAI Model
 *
 * This class provides a basic interface to the OpenAI API
 */
abstract class AbstractOpenAIModel extends AbstractGenericModel
{
    protected $apiurl = 'https://api.openai.com/v1/';

    protected function getHttpClient()
    {
        $http = parent::getHttpClient();
        $orgKey = $this->getFromConf('org', '');
        if ($orgKey) {
            $this->http->headers['OpenAI-Organization'] = $orgKey;
        }

        return $http;
    }
}
