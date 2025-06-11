<?php

namespace dokuwiki\plugin\aichat\test;

use dokuwiki\plugin\aichat\Model\ChatInterface;
use dokuwiki\plugin\aichat\Model\EmbeddingInterface;
use DokuWikiTest;

/**
 * Base for Model provider tests
 *
 * @group plugin_aichat
 * @group plugins
 * @group internet
 */
abstract class AbstractModelTest extends DokuWikiTest
{
    /** @inheritdoc */
    protected $pluginsEnabled = ['aichat'];

    /** @var string The provider name, e.g. 'openai', 'reka', etc. */
    protected string $provider;

    /** @var string The environment variable name for the API key */
    protected string $api_key_env;

    /** @var string The chat model name, e.g. 'gpt-3.5-turbo', 'gpt-4', etc. */
    protected string $chat_model;

    /** @var string The embedding model name, e.g. 'text-embedding-3-small', etc. */
    protected string $embedding_model;

    /** @inheritdoc */
    public function setUp(): void
    {
        parent::setUp();
        global $conf;

        $apikey = getenv($this->api_key_env);
        if (!$apikey) {
            $this->markTestSkipped('API key environment not set');
        }

        $providerName = ucfirst($this->provider);
        $providerConf = strtolower($this->provider);

        $conf['plugin']['aichat']['chatmodel'] = $providerName . ' ' . $this->chat_model;
        $conf['plugin']['aichat']['rephrasemodel'] = $providerName . ' ' . $this->chat_model;
        $conf['plugin']['aichat']['embedmodel'] = $providerName . ' ' . $this->embedding_model;
        $conf['plugin']['aichat'][$providerConf . '_apikey'] = $apikey;
    }

    public function testChat()
    {
        $prompt = 'This is a test. Please reply with "Hello World"';

        /** @var \helper_plugin_aichat $helper */
        $helper = plugin_load('helper', 'aichat');
        $model = $helper->getChatModel();

        $this->assertInstanceOf(ChatInterface::class, $model, 'Model should implement ChatInterface');
        $this->assertEquals(
            'dokuwiki\\plugin\\aichat\\Model\\' . $this->provider . '\\ChatModel',
            get_class($model),
            'Model seems to be the wrong class'
        );

        try {
            $reply = $model->getAnswer([
                ['role' => 'user', 'content' => $prompt]
            ]);
        } catch (\Exception $e) {
            if (preg_match('/(credit|fund|balance)/i', $e->getMessage())) {
                $this->markTestIncomplete($e->getMessage());
            } else {
                throw $e;
            }
        }

        $this->assertStringContainsString('hello world', strtolower($reply));
    }

    public function testEmbedding()
    {
        $text = 'This is a test for embeddings.';

        /** @var \helper_plugin_aichat $helper */
        $helper = plugin_load('helper', 'aichat');
        $model = $helper->getEmbeddingModel();

        $this->assertInstanceOf(EmbeddingInterface::class, $model, 'Model should implement EmbeddingInterface');
        $this->assertEquals(
            'dokuwiki\\plugin\\aichat\\Model\\' . $this->provider . '\\EmbeddingModel',
            get_class($model),
            'Model seems to be the wrong class'
        );

        try {
            $embedding = $model->getEmbedding($text);
        } catch (\Exception $e) {
            if (preg_match('/(credit|fund|balance)/i', $e->getMessage())) {
                $this->markTestIncomplete($e->getMessage());
            } else {
                throw $e;
            }
        }

        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding, 'Embedding should not be empty');
        $this->assertIsFloat($embedding[0], 'Embedding should be an array of floats');
    }
}
