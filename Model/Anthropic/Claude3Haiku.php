<?php

namespace dokuwiki\plugin\aichat\Model\Anthropic;

use dokuwiki\plugin\aichat\Model\ChatInterface;


/**
 * The Claude 3 Haiku model
 */
class Claude3Haiku extends ChatModel implements ChatInterface
{

    /** @inheritdoc */
    public function getModelName()
    {
        return 'claude-3-haiku-20240307';
    }

    /** @inheritdoc */
    public function get1MillionTokenPrice()
    {
        // differs between input and output tokens, we use the more expensive one
        return 1.25;
    }

    /** @inheritdoc */
    public function getMaxContextTokenLength()
    {
        return 3500;
    }

    /** @inheritdoc */
    public function getMaxRephrasingTokenLength()
    {
        return 3500;
    }

    /** @inheritdoc */
    public function getMaxEmbeddingTokenLength()
    {
        return 1000;
    }

}
