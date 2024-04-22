<?php

namespace dokuwiki\plugin\aichat\Model\Ollama;

use dokuwiki\plugin\aichat\Model\ChatInterface;

class ChatModel extends AbstractOllama implements ChatInterface
{
    /** @inheritdoc */
    public function getAnswer(array $messages): string
    {
        $data = [
            'messages' => $messages,
            'model' => $this->getModelName(),
            'stream' => false,
        ];
        $response = $this->request('chat', $data);
        return $response['message']['content'];
    }
}
