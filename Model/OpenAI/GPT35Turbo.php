<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

use dokuwiki\plugin\aichat\Model\ChatInterface;

/**
 *
 */
class GPT35Turbo extends AbstractOpenAIModel implements ChatInterface
{

    /** @inheritdoc */
    public function getModelName()
    {
        return 'gpt-3.5-turbo';
    }

    public function getMaxInputTokenLength(): int
    {
        return 16_385;
    }

    public function getInputTokenPrice(): float
    {
        return 0.50;
    }

    public function getMaxOutputTokenLength(): int
    {
        return 4_096;
    }

    public function getOutputTokenPrice(): float
    {
        return 1.50;
    }

    /** @inheritdoc */
    public function getAnswer($messages): string
    {
        $data = [
            'messages' => $messages,
            'model' => $this->getModelName(),
            'max_tokens' => null,
            'stream' => false,
            'n' => 1, // number of completions
            'temperature' => 0.0,
        ];
        $response = $this->request('chat/completions', $data);
        return $response['choices'][0]['message']['content'];
    }


}
