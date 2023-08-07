<?php

namespace dokuwiki\plugin\aichat\Model\OpenAI;

/**
 * OpenAI Client to use the GPT-4 model
 *
 * Chunks are slightly larger for this model
 */
class GPT4 extends GPT35Turbo
{
    static protected $setup = [
        'embedding' => ['text-embedding-ada-002', 2000],
        'rephrase' => ['gpt-4', 3500],
        'chat' => ['gpt-4', 3000],
    ];
}
