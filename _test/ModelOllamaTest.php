<?php

namespace dokuwiki\plugin\aichat\test;

/**
 * Ollama Model Test
 *
 * @group plugin_aichat
 * @group plugins
 * @group internet
 */
class ModelOllamaTest extends AbstractModelTest
{
    protected string $provider = 'Ollama';
    protected string $api_key_env = '';
    protected string $chat_model = 'llama3.2';
    protected string $embedding_model = 'nomic-embed-text';
}
