<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

/**
 * OpenAI Client to use the larger GPT-3.5-16k model
 *
 * Chunks are larger for this model
 */
class GPT35Turbo16K extends GPT35Turbo
{
    /** @inheritdoc */
    public function getModelName()
    {
        return 'gpt-3.5-turbo';
    }

    /** @inheritdoc */
    public function get1MillionTokenPrice()
    {
        // differs between input and output tokens, we use the more expensive one
        return 1.50;
    }

    /** @inheritdoc */
    public function getMaxContextTokenLength()
    {
        return 6000;
    }

    /** @inheritdoc */
    public function getMaxRephrasingTokenLength()
    {
        return 3500;
    }

    /** @inheritdoc */
    public function getMaxEmbeddingTokenLength()
    {
        return 3000;
    }
}
