<?php

namespace dokuwiki\plugin\aichat\test;

/**
 * Groq Model Test
 *
 * @group plugin_aichat
 * @group plugins
 * @group internet
 */
class ModelGroqTest extends AbstractModelTest
{
    protected string $provider = 'Groq';
    protected string $api_key_env = 'GROQ_API_KEY';
    protected string $chat_model = 'llama3-8b-8192';
    protected string $embedding_model = 'llama3-8b-8192';
}
