<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\plugin\aichat\Model\EmbeddingInterface;

class Embedding3Small extends EmbeddingAda02 implements EmbeddingInterface
{
    /** @inheritdoc */
    public function getModelName()
    {
        return 'text-embedding-3-small';
    }

    public function getMaxInputTokenLength(): int
    {
        return 8192;
    }

    public function getInputTokenPrice(): float
    {
        return 0.02;
    }

    /** @inheritdoc */
    public function getDimensions(): int
    {
        return 1536;
    }

}
