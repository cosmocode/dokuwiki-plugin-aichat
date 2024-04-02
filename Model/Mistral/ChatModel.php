<?php

namespace dokuwiki\plugin\aichat\Model\Mistral;

use dokuwiki\plugin\aichat\Model\ChatInterface;

class ChatModel extends AbstractMistralModel implements ChatInterface
{
    /** @inheritdoc */
    public function getAnswer(array $messages): string
    {
        // Mistral allows only for a system message at the beginning of the chat
        // https://discord.com/channels/1144547040454508606/1220314306844037150
        $system = '';
        $chat = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system .= $message['content'] . "\n";
            } else {
                $chat[] = $message;
            }
        }
        $system = trim($system);
        if ($system) {
            array_unshift($chat, ['role' => 'system', 'content' => $system]);
        }


        $data = [
            'messages' => $chat,
            'model' => $this->getModelName(),
            'max_tokens' => null,
            'stream' => false,
            'temperature' => 0.0,
        ];
        $response = $this->request('chat/completions', $data);
        return $response['choices'][0]['message']['content'];
    }
}
