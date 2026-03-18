<?php

namespace dokuwiki\plugin\aichat\test;

/**
 * Antrhopic Model Test
 *
 * @group plugin_aichat
 * @group plugins
 * @group internet
 */
class ModelAnthropicTest extends AbstractModelTest
{
    protected string $provider = 'Anthropic';
    protected string $api_key_env = 'ANTHROPIC_API_KEY';
    protected string $chat_model = 'claude-3-5-haiku-20241022';
    protected string $embedding_model = '';

    /** @inheritdoc */
    public function testEmbedding()
    {
        $this->markTestSkipped('Anthropic does not support embeddings yet');
    }
}
