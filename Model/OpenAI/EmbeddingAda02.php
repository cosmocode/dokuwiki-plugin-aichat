<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\plugin\aichat\Model\EmbeddingInterface;

class EmbeddingAda02 extends AbstractOpenAIModel implements EmbeddingInterface
{
    /** @inheritdoc */
    public function getModelName()
    {
        return 'text-embedding-ada-002';
    }

    public function getMaxInputTokenLength(): int
    {
        return 8192;
    }

    public function getInputTokenPrice(): float
    {
        return 0.10;
    }

    /** @inheritdoc */
    public function getDimensions(): int
    {
        return 1536;
    }

    /** @inheritdoc */
    public function getEmbedding($text): array
    {
        $data = [
            'model' => $this->getModelName(),
            'input' => [$text],
        ];
        $response = $this->request('embeddings', $data);

        return $response['data'][0]['embedding'];
    }


}
