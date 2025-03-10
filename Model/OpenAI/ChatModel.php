<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\plugin\aichat\Model\ChatInterface;

class ChatModel extends AbstractOpenAIModel implements ChatInterface
{
    /** @inheritdoc */
    public function getAnswer(array $messages): string
    {
        $data = [
            'messages' => $messages,
            'model' => $this->getModelName(),
            'max_completion_tokens' => null,
            'stream' => false,
            'n' => 1, // number of completions
        ];

        // resoning models o1, o1-mini, o3-mini do not support setting temperature
        // for all others we want a low temperature to get more coherent answers
        if(!str_starts_with($this->getModelName(), 'o')) {
            $data['temperature'] = 0.0;
        }

        $response = $this->request('chat/completions', $data);
        return $response['choices'][0]['message']['content'];
    }
}
