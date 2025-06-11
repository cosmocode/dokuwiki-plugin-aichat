<?php

namespace dokuwiki\plugin\aichat\test;

/**
 * Mistral Model Test
 *
 * @group plugin_aichat
 * @group plugins
 * @group internet
 */
class ModelMistralTest extends AbstractModelTest
{
    protected string $provider = 'Mistral';
    protected string $api_key_env = 'MISTRAL_API_KEY';
    protected string $chat_model = 'mistral-small-latest';
    protected string $embedding_model = 'mistral-embed';
}
