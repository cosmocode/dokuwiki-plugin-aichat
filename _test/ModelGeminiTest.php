<?php

namespace dokuwiki\plugin\aichat\test;

/**
 * Gemini Model Test
 *
 * @group plugin_aichat
 * @group plugins
 * @group internet
 */
class ModelGeminiTest extends AbstractModelTest
{
    protected string $provider = 'Gemini';
    protected string $api_key_env = 'GEMINI_API_KEY';
    protected string $chat_model = 'gemini-1.5-flash';
    protected string $embedding_model = 'text-embedding-004';
}
