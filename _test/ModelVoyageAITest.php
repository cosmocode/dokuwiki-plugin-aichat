<?php

namespace dokuwiki\plugin\aichat\test;

/**
 * VoyageAI Model Test
 *
 * @group plugin_aichat
 * @group plugins
 * @group internet
 */
class ModelVoyageAITest extends AbstractModelTest
{
    protected string $provider = 'VoyageAI';
    protected string $api_key_env = 'VOYAGEAI_API_KEY';
    protected string $chat_model = '';
    protected string $embedding_model = 'voyage-3';

    /** @inheritdoc */
    public function testChat()
    {
        $this->markTestSkipped('VoyageAI does not support chat models');
    }
}
