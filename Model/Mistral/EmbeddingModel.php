<?php

namespace dokuwiki\plugin\aichat\Model\Mistral;

use dokuwiki\plugin\aichat\Model\EmbeddingInterface;

class EmbeddingModel extends AbstractMistralModel implements EmbeddingInterface
{
    /** @inheritdoc */
    public function getEmbedding($text): array
    {
        $data = [
            'model' => $this->getModelName(),
            'input' => [$text],
            "encoding_format" => "float",
        ];
        $response = $this->request('embeddings', $data);

        return $response['data'][0]['embedding'];
    }
}
