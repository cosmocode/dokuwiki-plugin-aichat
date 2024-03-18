<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\plugin\aichat\Model\EmbeddingInterface;

class Embedding3Small extends AbstractOpenAIModel implements EmbeddingInterface
{
    /** @inheritdoc */
    public function getModelName()
    {
        return 'text-embedding-3-small';
    }

    /** @inheritdoc */
    public function get1MillionTokenPrice()
    {
        return 0.02;
    }

    /** @inheritdoc */
    public function getMaxEmbeddingTokenLength()
    {
        return 8000; // really 8191
    }

    /** @inheritdoc */
    public function getDimensions()
    {
        return 1536;
    }

    /** @inheritdoc */
    public function getEmbedding($text)
    {
        $data = [
            'model' => $this->getModelName(),
            'input' => [$text],
        ];
        $response = $this->request('embeddings', $data);

        return $response['data'][0]['embedding'];
    }
}
