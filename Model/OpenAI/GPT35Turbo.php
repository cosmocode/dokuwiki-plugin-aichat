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

    /** @inheritdoc */
    public function get1MillionTokenPrice()
    {
        // differs between input and output tokens, we use the more expensive one
        return 1.50;
    }

    /** @inheritdoc */
    public function getMaxContextTokenLength()
    {
        return 3500;
    }

    /** @inheritdoc */
    public function getMaxRephrasingTokenLength()
    {
        return 3500;
    }

    /** @inheritdoc */
    public function getMaxEmbeddingTokenLength()
    {
        return 1000;
    }

    /** @inheritdoc */
    public function getAnswer($messages)
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
