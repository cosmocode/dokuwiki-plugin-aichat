<?php

use dokuwiki\plugin\aichat\Embeddings;
use dokuwiki\plugin\aichat\OpenAI;

/**
 * DokuWiki Plugin aichat (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class helper_plugin_aichat_prompt extends \dokuwiki\Extension\Plugin
{

    /**
     * Ask a single standalone question
     * 
     * @param string $question
     * @return array
     * @throws Exception
     */
    public function askQuestion($question)
    {
        $openAI = new OpenAI($this->getConf('openaikey'), $this->getConf('openaiorg'));
        $embeddings = new Embeddings($openAI);
        $similar = $embeddings->getSimilarChunks($question);
        $context = implode("\n", array_column($similar, 'text'));

        $prompt = $this->localFN('prompt_question');
        $messages = [
            [
                'role' => 'system',
                'content' => implode("\n", [$prompt, $context])
            ],
            [
                'role' => 'user',
                'content' => $question
            ]
        ];

        $answer = $openAI->getChatAnswer($messages);

        return [
            'question' => $question,
            'answer' => $answer,
            'sources' => $similar,
        ];
    }
}

