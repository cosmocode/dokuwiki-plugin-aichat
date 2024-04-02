<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\plugin\aichat\Model\EmbeddingInterface;

class EmbeddingModel extends AbstractOpenAIModel implements EmbeddingInterface
{
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
