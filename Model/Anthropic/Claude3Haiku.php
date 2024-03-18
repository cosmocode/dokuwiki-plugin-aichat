<?php

namespace dokuwiki\plugin\aichat\Model\Anthropic;

use dokuwiki\plugin\aichat\Model\ChatInterface;


/**
 * The Claude 3 Haiku model
 */
class Claude3Haiku extends AbstractAnthropicModel implements ChatInterface
{

    /** @inheritdoc */
    public function getModelName()
    {
        return 'claude-3-haiku-20240307';
    }

    /** @inheritdoc */
    public function get1MillionTokenPrice()
    {
        // differs between input and output tokens, we use the more expensive one
        return 1.25;
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
        // convert OpenAI Style to Anthropic style
        $system = '';
        $chat = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system .= $message['content']."\n";
            } else {
                $chat[] = $message;
            }
        }

        $data = [
            'messages' => $chat,
            'model' => $this->getModelName(),
            'max_tokens' => $this->getMaxEmbeddingTokenLength(),
            'stream' => false,
            'temperature' => 0.0,
        ];

        if($system) {
            $data['system'] = $system;
        }

        $response = $this->request('messages', $data);

        print_r($response);

        return $response['content'][0]['text'];
    }
}
