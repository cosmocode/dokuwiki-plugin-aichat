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
            'options' => [
                'num_ctx' => $this->getMaxInputTokenLength() ?: 512
            ]
        ];
        $response = $this->request('chat', $data);
        $content = $response['message']['content'];
        // remove thinking part from deepseek answers
        $content = preg_replace('/^<think>.*?(?:<\/think>)/s', '', $content);
        return $content;
    }
}
