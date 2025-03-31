<?php

use dokuwiki\ErrorHandler;
use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Logger;
use dokuwiki\plugin\aichat\Chunk;

/**
 * DokuWiki Plugin aichat (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class action_plugin_aichat extends ActionPlugin
{
    /** @inheritDoc */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handleQuestion');
    }


    /**
     * Event handler for AJAX_CALL_UNKNOWN event
     *
     * @see https://www.dokuwiki.org/devel:events:ajax_call_unknown
     * @param Event $event Event object
     * @param mixed $param optional parameter passed when event was registered
     * @return void
     */
    public function handleQuestion(Event $event, mixed $param)
    {
        if ($event->data !== 'aichat') return;
        $event->preventDefault();
        $event->stopPropagation();
        global $INPUT;

        /** @var helper_plugin_aichat $helper */
        $helper = plugin_load('helper', 'aichat');

        $question = $INPUT->post->str('question');
        $pagecontext = $INPUT->post->str('pagecontext');
        $history = json_decode((string)$INPUT->post->str('history'), null, 512, JSON_THROW_ON_ERROR);
        header('Content-Type: application/json');

        if (!$helper->userMayAccess()) {
            echo json_encode([
                'question' => $question,
                'answer' => $this->getLang('restricted'),
                'sources' => [],
            ], JSON_THROW_ON_ERROR);
            return;
        }

        try {
            $result = $helper->askChatQuestion($question, $history, $pagecontext);
            $sources = [];
            foreach ($result['sources'] as $source) {
                /** @var Chunk $source */
                if (isset($sources[$source->getPage()])) continue; // only show the first occurrence per page
                $sources[$source->getPage()] = [
                    'page' => $source->getPage(),
                    'url' => wl($source->getPage()),
                    'title' => p_get_first_heading($source->getPage()) ?: $source->getPage(),
                    'score' => sprintf("%.2f%%", $source->getScore() * 100),
                ];
            }
            $parseDown = new Parsedown();
            $parseDown->setSafeMode(true);

            echo json_encode([
                'question' => $result['question'],
                'answer' => $parseDown->text($result['answer']),
                'sources' => array_values($sources),
            ], JSON_THROW_ON_ERROR);

            if ($this->getConf('logging')) {
                Logger::getInstance('aichat')->log(
                    $question,
                    [
                        'interpretation' => $result['question'],
                        'answer' => $result['answer'],
                        'sources' => $sources,
                        'ip' => $INPUT->server->str('REMOTE_ADDR'),
                        'user' => $INPUT->server->str('REMOTE_USER'),
                        'stats' => $helper->getChatModel()->getUsageStats()
                    ]
                );
            }
        } catch (\Exception $e) {
            ErrorHandler::logException($e);
            echo json_encode([
                'question' => $question,
                'answer' => 'An error occurred. More info may be available in the error log. ' . $e->getMessage(),
                'sources' => [],
            ], JSON_THROW_ON_ERROR);
        }
    }
}
