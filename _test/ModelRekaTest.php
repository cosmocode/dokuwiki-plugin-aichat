<?php

namespace dokuwiki\plugin\aichat\test;

/**
 * Reka Model Test
 *
 * @group plugin_aichat
 * @group plugins
 * @group internet
 */
class ModelRekaTest extends AbstractModelTest
{
    protected string $provider = 'Reka';
    protected string $api_key_env = 'REKA_API_KEY';
    protected string $chat_model = 'reka-core';
    protected string $embedding_model = '';

    /** @inheritdoc */
    public function testEmbedding()
    {
        $this->markTestSkipped('Reka does not support embeddings yet');
    }
}
