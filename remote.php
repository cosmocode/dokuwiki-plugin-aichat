<?php

use dokuwiki\Extension\RemotePlugin;
use dokuwiki\plugin\aichat\RemoteResponse\LlmReply;
use dokuwiki\Remote\AccessDeniedException;

/**
 * DokuWiki Plugin aichat (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Andreas Gohr <gohr@cosmocode.de>
 */
class remote_plugin_aichat extends RemotePlugin
{
    /**
     *
     * @param string $query The question to ask the LLM
     * @param string $model The model to use, if empty the default model is used
     * @param string $lang Language code to override preferUIlanguage setting. "auto" to force autodetection.
     * @return LlmReply
     */
    public function ask($query, $model = '', $lang = '')
    {
        /** @var helper_plugin_aichat $helper */
        $helper = plugin_load('helper', 'aichat');
        if ($model) {
            $helper->updateConfig(
                ['chatmodel' => $model, 'rephasemodel' => $model]
            );
        }

        if (!$helper->userMayAccess()) {
            throw new AccessDeniedException('You are not allowed to use this plugin', 111);
        }

        if ($lang === 'auto') {
            $helper->updateConfig(['preferUIlanguage' => 0]);
        } elseif ($lang) {
            $helper->updateConfig(['preferUIlanguage' => 1]);
            global $conf;
            $conf['lang'] = $lang;
        }

        $result = $helper->askQuestion($query);

        return new LlmReply($result);
    }
}
