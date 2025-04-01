<?php

namespace dokuwiki\plugin\aichat\Model\Gemini;

use dokuwiki\plugin\aichat\Model\ChatInterface;

class ChatModel extends AbstractGeminiModel implements ChatInterface
{
    /** @inheritdoc */
    public function getAnswer(array $messages): string
    {
        // Gemini payload is weird, we convert OpenAI style here
        $data = [
            'contents' => [],
        ];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                // system messages go to extra array
                if (!isset($data['system_instructions'])) {
                    $data['system_instructions'] = [];
                    $data['system_instructions']['parts'] = [];
                }
                $data['system_instructions']['parts'][] = ['text' => $message['content']];
            } else {
                $data['contents'][] = [
                    'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [
                        ['text' => $message['content']]
                    ]
                ];
            }
        }

        $response = $this->request($this->getModelName(), 'generateContent', $data);
        return $response['candidates'][0]['content']['parts'][0]['text'];
    }
}
