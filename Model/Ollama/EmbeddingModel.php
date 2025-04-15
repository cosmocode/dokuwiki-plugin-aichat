<?php

namespace dokuwiki\plugin\aichat\Model\Ollama;

use dokuwiki\plugin\aichat\Model\EmbeddingInterface;

class EmbeddingModel extends AbstractOllama implements EmbeddingInterface
{
    /** @inheritdoc */
    public function getEmbedding($text): array
    {
        $data = [
            'model' => $this->getModelName(),
            'input' => $text,
            'options' => [
                'num_ctx' => $this->getMaxInputTokenLength() ?: 512,
            ]
        ];
        $response = $this->request('embed', $data);
        return $response['embeddings'][0] ?? [];
    }
}
