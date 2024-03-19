<?php

namespace dokuwiki\plugin\aichat\Model\Mistral;

use dokuwiki\plugin\aichat\Model\ChatInterface;

class ChatModel extends AbstractMistralModel implements ChatInterface
{
    /** @inheritdoc */
    public function getAnswer(array $messages): string
    {
        $data = [
            'messages' => $messages,
            'model' => $this->getModelName(),
            'max_tokens' => null,
            'stream' => false,
            'temperature' => 0.0,
        ];
        $response = $this->request('chat/completions', $data);
        return $response['choices'][0]['message']['content'];
    }
}
