<?php

namespace dokuwiki\plugin\aichat\test;

use dokuwiki\HTTP\DokuHTTPClient;

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

    public function setUp(): void
    {
        global $conf;
        parent::setUp();

        $conf['plugin']['aichat']['ollama_apiurl'] = 'http://localhost:11434/api';
        $url = $conf['plugin']['aichat']['ollama_apiurl'] . '/version';

        $http = new DokuHTTPClient();
        $http->timeout = 4;
        $result = $http->get($url);

        if (!$result) $this->markTestSkipped('Local Ollama server seems not to be available.');
    }
}
