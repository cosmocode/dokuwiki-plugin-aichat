<?php

use dokuwiki\ErrorHandler;
use dokuwiki\plugin\aichat\Chunk;

/**
 * DokuWiki Plugin aichat (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class action_plugin_aichat extends \dokuwiki\Extension\ActionPlugin
{

    /** @inheritDoc */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handleQuestion');
    }


    /**
     * Event handler for AJAX_CALL_UNKNOWN event
     *
     * @see https://www.dokuwiki.org/devel:events:ajax_call_unknown
     * @param Doku_Event $event Event object
     * @param mixed $param optional parameter passed when event was registered
     * @return void
     */
    public function handleQuestion(Doku_Event $event, $param)
    {
        if ($event->data !== 'aichat') return;
        $event->preventDefault();
        $event->stopPropagation();
        global $INPUT;

        /** @var helper_plugin_aichat $helper */
        $helper = plugin_load('helper', 'aichat');

        $question = $INPUT->post->str('question');
        $history = json_decode($INPUT->post->str('history'));
        header('Content-Type: application/json');

        if (!$helper->userMayAccess()) {
            echo json_encode([
                'question' => $question,
                'answer' => $this->getLang('restricted'),
                'sources' => [],
            ]);
            return;
        }

        try {
            $result = $helper->askChatQuestion($question, $history);
            $sources = [];
            foreach ($result['sources'] as $source) {
                /** @var Chunk $source */
                $sources[wl($source->getPage())] = p_get_first_heading($source->getPage()) ?: $source->getPage();
            }
            $parseDown = new Parsedown();
            $parseDown->setSafeMode(true);

            echo json_encode([
                'question' => $result['question'],
                'answer' => $parseDown->text($result['answer']),
                'sources' => $sources,
            ]);

            if ($this->getConf('logging')) {
                \dokuwiki\Logger::getInstance('aichat')->log(
                    $question,
                    [
                        'interpretation' => $result['question'],
                        'answer' => $result['answer'],
                        'sources' => $sources,
                        'ip' => $INPUT->server->str('REMOTE_ADDR'),
                        'user' => $INPUT->server->str('REMOTE_USER'),
                        'stats' => $helper->getModel()->getUsageStats()
                    ]
                );
            }
        } catch (\Exception $e) {
            ErrorHandler::logException($e);
            echo json_encode([
                'question' => $question,
                'answer' => 'An error occurred. More info may be available in the error log. ' . $e->getMessage(),
                'sources' => [],
            ]);
        }
    }

}

