<?php

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

        $question = $INPUT->post->str('question');
        $history = json_decode($INPUT->post->str('history'));

        /** @var helper_plugin_aichat $helper */
        $helper = plugin_load('helper', 'aichat');

        $result = $helper->askChatQuestion($question, $history);

        $sources = [];
        foreach ($result['sources'] as $source) {
            $sources[wl($source['meta']['pageid'])] = p_get_first_heading($source['meta']['pageid']) ?:
                $source['meta']['pageid'];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'question' => $result['question'],
            'answer' => $result['answer'],
            'sources' => $sources,
        ]);
    }

}

