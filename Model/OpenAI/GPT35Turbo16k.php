<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

/**
 * OpenAI Client to use the larger GPT-3.5-16k model
 *
 * Chunks are larger for this model
 */
class GPT35Turbo16K extends GPT35Turbo
{
    static protected $setup = [
        'embedding' => ['text-embedding-ada-002', 3000],
        'rephrase' => ['gpt-3.5-turbo', 3500],
        'chat' => ['gpt-3.5-turbo-16k', 1500],
    ];
}
