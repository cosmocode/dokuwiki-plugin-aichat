<?php

use dokuwiki\plugin\aichat\Embeddings;
use dokuwiki\plugin\aichat\OpenAI;
use TikToken\Encoder;

/**
 * DokuWiki Plugin aichat (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class helper_plugin_aichat_prompt extends \dokuwiki\Extension\Plugin
{
    /** @var OpenAI */
    protected $openAI;
    /** @var Embeddings */
    protected $embeddings;

    public function __construct()
    {
        $this->openAI = new OpenAI($this->getConf('openaikey'), $this->getConf('openaiorg'));
        $this->embeddings = new Embeddings($this->openAI);
    }


    /**
     * Ask a single standalone question
     *
     * @param string $question
     * @return array
     * @throws Exception
     */
    public function askQuestion($question)
    {
        $similar = $this->embeddings->getSimilarChunks($question);
        $context = implode("\n", array_column($similar, 'text'));

        $prompt = $this->getPrompt('question', ['context' => $context]);
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

        $answer = $this->openAI->getChatAnswer($messages);

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
        $tiktok = new Encoder();
        $chatHistory = '';
        $history = array_reverse($history);
        foreach ($history as $row) {
            if (count($tiktok->encode($chatHistory)) > 3000) {
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
        return $this->openAI->getChatAnswer($messages);
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

