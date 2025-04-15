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

    /** @inheritdoc */
    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);

        $orgKey = $this->getFromConf($config, 'org', '');

        if ($orgKey) {
            $this->http->headers['OpenAI-Organization'] = $orgKey;
        }
    }
}
