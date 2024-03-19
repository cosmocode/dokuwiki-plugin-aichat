<?php

use dokuwiki\Extension\CLIPlugin;
use dokuwiki\Extension\Plugin;
use dokuwiki\plugin\aichat\AIChat;
use dokuwiki\plugin\aichat\Chunk;
use dokuwiki\plugin\aichat\Embeddings;
use dokuwiki\plugin\aichat\Model\ChatInterface;
use dokuwiki\plugin\aichat\Model\EmbeddingInterface;
use dokuwiki\plugin\aichat\Model\OpenAI\Embedding3Small;
use dokuwiki\plugin\aichat\Storage\AbstractStorage;

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
    /** @var ChatInterface */
    protected $chatModel;
    /** @var EmbeddingInterface */
    protected $embedModel;
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
        $this->loadConfig();
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
     * Access the Chat Model
     *
     * @return ChatInterface
     */
    public function getChatModel()
    {
        if ($this->chatModel instanceof ChatInterface) {
            return $this->chatModel;
        }

        [$namespace, $name] = sexplode(' ', $this->getConf('chatmodel'), 2);
        $class = '\\dokuwiki\\plugin\\aichat\\Model\\' . $namespace . '\\ChatModel';

        if (!class_exists($class)) {
            throw new \RuntimeException('No ChatModel found for ' . $namespace);
        }

        $this->chatModel = new $class($name, $this->conf);
        return $this->chatModel;
    }

    /**
     * Access the Embedding Model
     *
     * @return EmbeddingInterface
     */
    public function getEmbedModel()
    {
        if ($this->embedModel instanceof EmbeddingInterface) {
            return $this->embedModel;
        }

        [$namespace, $name] = sexplode(' ', $this->getConf('embedmodel'), 2);
        $class = '\\dokuwiki\\plugin\\aichat\\Model\\' . $namespace . '\\EmbeddingModel';

        if (!class_exists($class)) {
            throw new \RuntimeException('No EmbeddingModel found for ' . $namespace);
        }

        $this->embedModel = new $class($name, $this->conf);
        return $this->embedModel;
    }


    /**
     * Access the Embeddings interface
     *
     * @return Embeddings
     */
    public function getEmbeddings()
    {
        if ($this->embeddings instanceof Embeddings) {
            return $this->embeddings;
        }

        $this->embeddings = new Embeddings(
            $this->getChatModel(),
            $this->getEmbedModel(),
            $this->getStorage(),
            $this->conf
        );
        if ($this->logger) {
            $this->embeddings->setLogger($this->logger);
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
        if ($this->storage instanceof AbstractStorage) {
            return $this->storage;
        }

        $class = '\\dokuwiki\\plugin\\aichat\\Storage\\' . $this->getConf('storage') . 'Storage';
        $this->storage = new $class($this->conf);

        if ($this->logger) {
            $this->storage->setLogger($this->logger);
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
        return $this->askQuestion($standaloneQuestion, $history);
    }

    /**
     * Ask a single standalone question
     *
     * @param string $question
     * @param array $history [user, ai] of the previous question
     * @return array ['question' => $question, 'answer' => $answer, 'sources' => $sources]
     * @throws Exception
     */
    public function askQuestion($question, $history = [])
    {
        $similar = $this->getEmbeddings()->getSimilarChunks($question, $this->getLanguageLimit());
        if ($similar) {
            $context = implode(
                "\n",
                array_map(static fn(Chunk $chunk) => "\n```\n" . $chunk->getText() . "\n```\n", $similar)
            );
            $prompt = $this->getPrompt('question', [
                'context' => $context,
            ]);
        } else {
            $prompt = $this->getPrompt('noanswer');
            $history = [];
        }

        $messages = $this->prepareMessages($prompt, $question, $history);
        $answer = $this->getChatModel()->getAnswer($messages);

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
        $prompt = $this->getPrompt('rephrase');
        $messages = $this->prepareMessages($prompt, $question, $history);
        return $this->getChatModel()->getAnswer($messages);
    }

    /**
     * Prepare the messages for the AI
     *
     * @param string $prompt The fully prepared system prompt
     * @param string $question The user question
     * @param array[] $history The chat history [[user, ai], [user, ai], ...]
     * @return array An OpenAI compatible array of messages
     */
    protected function prepareMessages($prompt, $question, $history)
    {
        // calculate the space for context
        $remainingContext = $this->getChatModel()->getMaxInputTokenLength();
        $remainingContext -= $this->countTokens($prompt);
        $remainingContext -= $this->countTokens($question);
        $safetyMargin = $remainingContext * 0.05; // 5% safety margin
        $remainingContext -= $safetyMargin;
        // FIXME we may want to also have an upper limit for the history and not always use the full context

        $messages = $this->historyMessages($history, $remainingContext);
        $messages[] = [
            'role' => 'system',
            'content' => $prompt
        ];
        $messages[] = [
            'role' => 'user',
            'content' => $question
        ];
        return $messages;
    }

    /**
     * Create an array of OpenAI compatible messages from the given history
     *
     * Only as many messages are used as fit into the token limit
     *
     * @param array[] $history The chat history [[user, ai], [user, ai], ...]
     * @param int $tokenLimit
     * @return array
     */
    protected function historyMessages($history, $tokenLimit)
    {
        $remainingContext = $tokenLimit;

        $messages = [];
        $history = array_reverse($history);
        foreach ($history as $row) {
            $length = $this->countTokens($row[0] . $row[1]);
            if ($length > $remainingContext) {
                break;
            }
            $remainingContext -= $length;

            $messages[] = [
                'role' => 'assistant',
                'content' => $row[1]
            ];
            $messages[] = [
                'role' => 'user',
                'content' => $row[0]
            ];
        }
        return array_reverse($messages);
    }

    /**
     * Get an aproximation of the token count for the given text
     *
     * @param $text
     * @return int
     */
    protected function countTokens($text)
    {
        return count($this->getEmbeddings()->getTokenEncoder()->encode($text));
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
        $vars['language'] = $this->getLanguagePrompt();

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

        $languagePrompt = 'Always answer in the user\'s language. ' .
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
     * @param array $data
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
