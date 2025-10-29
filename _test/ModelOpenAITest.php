<?php

namespace dokuwiki\plugin\aichat\test;

/**
 * OpenAI Model Test
 *
 * @group plugin_aichat
 * @group plugins
 * @group internet
 */
class ModelOpenAITest extends AbstractModelTest
{
    protected string $provider = 'OpenAI';
    protected string $api_key_env = 'OPENAI_API_KEY';
    protected string $chat_model = 'gpt-3.5-turbo';
    protected string $embedding_model = 'text-embedding-3-small';
}
