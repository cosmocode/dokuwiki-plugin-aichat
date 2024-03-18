<?php

use dokuwiki\Extension\CLIPlugin;
use dokuwiki\Extension\Plugin;
use dokuwiki\plugin\aichat\AIChat;
use dokuwiki\plugin\aichat\Chunk;
use dokuwiki\plugin\aichat\Embeddings;
use dokuwiki\plugin\aichat\Model\AbstractModel;
use dokuwiki\plugin\aichat\Model\OpenAI\GPT35Turbo;
use dokuwiki\plugin\aichat\Storage\AbstractStorage;
use dokuwiki\plugin\aichat\Storage\ChromaStorage;
use dokuwiki\plugin\aichat\Storage\PineconeStorage;
use dokuwiki\plugin\aichat\Storage\QdrantStorage;
use dokuwiki\plugin\aichat\Storage\SQLiteStorage;

/**
 * DokuWiki Plugin aichat (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class helper_plugin_aichat extends Plugin
{
    /** @var CLIPlugin $logger */
    protected $logger;
    /** @var AbstractModel */
    protected $model;
    /** @var Embeddings */
    protected $embeddings;
    /** @var AbstractStorage */
    protected $storage;

    /** @var array where to store meta data on the last run */
    protected $runDataFile;

    /**
     * Constructor. Initializes vendor autoloader
     */
    public function __construct()
    {
        require_once __DIR__ . '/vendor/autoload.php'; // FIXME obsolete from Kaos onwards
        global $conf;
        $this->runDataFile = $conf['metadir'] . '/aichat__run.json';
    }

    /**
     * Use the given CLI plugin for logging
     *
     * @param CLIPlugin $logger
     * @return void
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

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
        if (!$this->model instanceof AbstractModel) {
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
        if (!$this->embeddings instanceof Embeddings) {
            $this->embeddings = new Embeddings($this->getModel(), $this->getStorage());
            if ($this->logger) {
                $this->embeddings->setLogger($this->logger);
            }
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
        if (!$this->storage instanceof AbstractStorage) {
            if ($this->getConf('pinecone_apikey')) {
                $this->storage = new PineconeStorage();
            } elseif ($this->getConf('chroma_baseurl')) {
                $this->storage = new ChromaStorage();
            } elseif ($this->getConf('qdrant_baseurl')) {
                $this->storage = new QdrantStorage();
            } else {
                $this->storage = new SQLiteStorage();
            }

            if ($this->logger) {
                $this->storage->setLogger($this->logger);
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
            $prev = end($history);
        } else {
            $standaloneQuestion = $question;
            $prev = [];
        }
        return $this->askQuestion($standaloneQuestion, $prev);
    }

    /**
     * Ask a single standalone question
     *
     * @param string $question
     * @param array $previous [user, ai] of the previous question
     * @return array ['question' => $question, 'answer' => $answer, 'sources' => $sources]
     * @throws Exception
     */
    public function askQuestion($question, $previous = [])
    {
        $similar = $this->getEmbeddings()->getSimilarChunks($question, $this->getLanguageLimit());
        if ($similar) {
            $context = implode(
                "\n",
                array_map(static fn(Chunk $chunk) => "\n```\n" . $chunk->getText() . "\n```\n", $similar)
            );
            $prompt = $this->getPrompt('question', [
                'context' => $context,
                'language' => $this->getLanguagePrompt()
            ]);
        } else {
            $prompt = $this->getPrompt('noanswer') . ' ' . $this->getLanguagePrompt();
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

        if ($previous) {
            array_unshift($messages, [
                'role' => 'assistant',
                'content' => $previous[1]
            ]);
            array_unshift($messages, [
                'role' => 'user',
                'content' => $previous[0]
            ]);
        }

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

        $replace = [];
        foreach ($vars as $key => $val) {
            $replace['{{' . strtoupper($key) . '}}'] = $val;
        }

        return strtr($template, $replace);
    }

    /**
     * Construct the prompt to define the answer language
     *
     * @return string
     */
    protected function getLanguagePrompt()
    {
        global $conf;
        $isoLangnames = include(__DIR__ . '/lang/languages.php');

        $currentLang = $isoLangnames[$conf['lang']] ?? 'English';

        if ($this->getConf('preferUIlanguage') > AIChat::LANG_AUTO_ALL) {
            if (isset($isoLangnames[$conf['lang']])) {
                $languagePrompt = 'Always answer in ' . $isoLangnames[$conf['lang']] . '.';
                return $languagePrompt;
            }
        }

        $languagePrompt = 'Always answer in the user\'s language.' .
            "If you are unsure about the language, speak $currentLang.";
        return $languagePrompt;
    }

    /**
     * Should sources be limited to current language?
     *
     * @return string The current language code or empty string
     */
    public function getLanguageLimit()
    {
        if ($this->getConf('preferUIlanguage') >= AIChat::LANG_UI_LIMITED) {
            global $conf;
            return $conf['lang'];
        } else {
            return '';
        }
    }

    /**
     * Store info about the last run
     *
     * @return void
     */
    public function setRunData(array $data)
    {
        file_put_contents($this->runDataFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Get info about the last run
     *
     * @return array
     */
    public function getRunData()
    {
        if (!file_exists($this->runDataFile)) {
            return [];
        }
        return json_decode(file_get_contents($this->runDataFile), true);
    }
}
