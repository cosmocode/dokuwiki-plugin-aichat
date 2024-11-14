<?php

namespace dokuwiki\plugin\aichat\Model\Gemini;

use dokuwiki\plugin\aichat\Model\EmbeddingInterface;

class EmbeddingModel extends AbstractGeminiModel implements EmbeddingInterface
{

    public function getEmbedding($text): array
    {

        $data = [
            'model' => $this->getModelName(),
            'content' => [
                'parts' => [
                    ['text' => $text]
                ]
            ]
        ];

        $response = $this->request($this->getModelName(), 'embedContent', $data);

        return $response['embedding']['values'];
    }
}
