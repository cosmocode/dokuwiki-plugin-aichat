<?php

/**
 * DokuWiki Plugin aichat (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class syntax_plugin_aichat_chat extends \dokuwiki\Extension\SyntaxPlugin
{
    /** @inheritDoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritDoc */
    public function getPType()
    {
        return 'block';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 155;
    }

    /** @inheritDoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('<aichat(?: [^>]+)*>.*?(?:<\/aichat>)', $mode, 'plugin_aichat_chat');
    }


    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $match = substr($match, 7, -9);
        [$params, $body] = explode('>', $match, 2);
        $params = explode(' ', $params);

        return ['params' => $params, 'body' => $body];
    }

    /** @inheritDoc */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        if ($format !== 'xhtml') {
            return false;
        }

        if($this->getConf('restricted')) $renderer->nocache();
        $helper = plugin_load('helper', 'aichat');
        if(!$helper->userMayAccess()) {
            return true;
        }

        $opts = [
            'hello' => trim($data['body']),
            'placeholder' => $this->getLang('placeholder'),
            'url' => DOKU_BASE . 'lib/exe/ajax.php?call=aichat',
        ];
        $html = '<aichat-chat ' . buildAttributes($opts) . '></aichat-chat>';

        if (in_array('button', $data['params'])) {
            $opts = [
                'label' => $this->getLang('title'),
            ];
            if(in_array('float', $data['params'])) $opts['class'] = 'float';

            $html = '<aichat-button ' . buildAttributes($opts) . '>' . $html . '</aichat-button>';
        }

        $renderer->doc .= $html;
        return true;
    }
}

