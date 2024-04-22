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
            'prompt' => $text,
        ];
        $response = $this->request('embeddings', $data);

        return $response['embedding'];
    }
}
