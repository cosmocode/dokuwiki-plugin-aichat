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
        return 'gpt-3.5-turbo-16k';
    }

    /** @inheritdoc */
    public function get1kTokenPrice()
    {
        return 0.003;
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
