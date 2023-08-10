<?php

use dokuwiki\plugin\aichat\Model\AbstractModel;
use dokuwiki\plugin\aichat\Chunk;
use dokuwiki\plugin\aichat\Embeddings;
use dokuwiki\plugin\aichat\Model\OpenAI\GPT35Turbo;
use dokuwiki\plugin\aichat\Storage\AbstractStorage;
use dokuwiki\plugin\aichat\Storage\PineconeStorage;
use dokuwiki\plugin\aichat\Storage\SQLiteStorage;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * DokuWiki Plugin aichat (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class helper_plugin_aichat extends \dokuwiki\Extension\Plugin
{
    /** @var AbstractModel */
    protected $model;
    /** @var Embeddings */
    protected $embeddings;
    /** @var AbstractStorage */
    protected $storage;

    /**
     * Check if the current user is allowed to use the plugin (if it has been restricted)
     *
     * @return bool
     */
    public function userMayAccess()
    {
        global $auth;
        global $USERINFO;
        global $INPUT;

        if (!$auth) return true;
        if (!$this->getConf('restrict')) return true;
        if (!isset($USERINFO)) return false;

        return auth_isMember($this->getConf('restrict'), $INPUT->server->str('REMOTE_USER'), $USERINFO['grps']);
    }

    /**
     * Access the OpenAI client
     *
     * @return GPT35Turbo
     */
    public function getModel()
    {
        if ($this->model === null) {
            $class = '\\dokuwiki\\plugin\\aichat\\Model\\' . $this->getConf('model');

            if (!class_exists($class)) {
                throw new \RuntimeException('Configured model not found: ' . $class);
            }
            // FIXME for now we only have OpenAI models, so we can hardcode the auth setup
            $this->model = new $class([
                'key' => $this->getConf('openaikey'),
                'org' => $this->getConf('openaiorg')
            ]);
        }

        return $this->model;
    }

    /**
     * Access the Embeddings interface
     *
     * @return Embeddings
     */
    public function getEmbeddings()
    {
        if ($this->embeddings === null) {
            // FIXME we currently have only one storage backend, so we can hardcode it
            $this->embeddings = new Embeddings($this->getModel(), $this->getStorage());
        }

        return $this->embeddings;
    }

    /**
     * Access the Storage interface
     *
     * @return AbstractStorage
     */
    public function getStorage()
    {
        if ($this->storage === null) {
            if($this->getConf('pinecone_apikey')) {
                $this->storage = new PineconeStorage();
            } else {
                $this->storage = new SQLiteStorage();
            }
        }

        return $this->storage;
    }

    /**
     * Ask a question with a chat history
     *
     * @param string $question
     * @param array[] $history The chat history [[user, ai], [user, ai], ...]
     * @return array ['question' => $question, 'answer' => $answer, 'sources' => $sources]
     * @throws Exception
     */
    public function askChatQuestion($question, $history = [])
    {
        if ($history) {
            $standaloneQuestion = $this->rephraseChatQuestion($question, $history);
        } else {
            $standaloneQuestion = $question;
        }
        return $this->askQuestion($standaloneQuestion);
    }

    /**
     * Ask a single standalone question
     *
     * @param string $question
     * @return array ['question' => $question, 'answer' => $answer, 'sources' => $sources]
     * @throws Exception
     */
    public function askQuestion($question)
    {
        $similar = $this->getEmbeddings()->getSimilarChunks($question);
        if ($similar) {
            $context = implode("\n", array_map(function (Chunk $chunk) {
                return "\n```\n" . $chunk->getText() . "\n```\n";
            }, $similar));
            $prompt = $this->getPrompt('question', ['context' => $context]);
        } else {
            $prompt = $this->getPrompt('noanswer');
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $prompt
            ],
            [
                'role' => 'user',
                'content' => $question
            ]
        ];

        $answer = $this->getModel()->getAnswer($messages);

        return [
            'question' => $question,
            'answer' => $answer,
            'sources' => $similar,
        ];
    }

    /**
     * Rephrase a question into a standalone question based on the chat history
     *
     * @param string $question The original user question
     * @param array[] $history The chat history [[user, ai], [user, ai], ...]
     * @return string The rephrased question
     * @throws Exception
     */
    public function rephraseChatQuestion($question, $history)
    {
        // go back in history as far as possible without hitting the token limit
        $chatHistory = '';
        $history = array_reverse($history);
        foreach ($history as $row) {
            if (
                count($this->getEmbeddings()->getTokenEncoder()->encode($chatHistory)) >
                $this->getModel()->getMaxRephrasingTokenLength()
            ) {
                break;
            }

            $chatHistory =
                "Human: " . $row[0] . "\n" .
                "Assistant: " . $row[1] . "\n" .
                $chatHistory;
        }

        // ask openAI to rephrase the question
        $prompt = $this->getPrompt('rephrase', ['history' => $chatHistory, 'question' => $question]);
        $messages = [['role' => 'user', 'content' => $prompt]];
        return $this->getModel()->getRephrasedQuestion($messages);
    }

    /**
     * Load the given prompt template and fill in the variables
     *
     * @param string $type
     * @param string[] $vars
     * @return string
     */
    protected function getPrompt($type, $vars = [])
    {
        $template = file_get_contents($this->localFN('prompt_' . $type));

        $replace = array();
        foreach ($vars as $key => $val) {
            $replace['{{' . strtoupper($key) . '}}'] = $val;
        }

        return strtr($template, $replace);
    }
}

