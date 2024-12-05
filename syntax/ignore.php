<?php

use dokuwiki\Extension\SyntaxPlugin;

/**
 * DokuWiki Plugin aichat (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Andreas Gohr <gohr@cosmocode.de>
 */
class syntax_plugin_aichat_ignore extends SyntaxPlugin
{
    /** @var string temporary store for the current document */
    protected $temporaryDoc;

    /** @inheritDoc */
    public function getType()
    {
        return 'formatting';
    }

    /** @inheritdoc */
    public function getAllowedTypes()
    {
        return [
            'container',
            'formatting',
            'substition',
            'protected',
            'disabled',
            'paragraphs',
            'baseonly',
        ];
    }

    /** @inheritDoc */
    public function getPType()
    {
        return 'normal';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 32;
    }

    /** @inheritDoc */
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<ai-ignore>', $mode, 'plugin_aichat_ignore');
    }

    /** @inheritDoc */
    public function postConnect()
    {
        $this->Lexer->addExitPattern('</ai-ignore>', 'plugin_aichat_ignore');
    }

    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $data = [
            'state' => $state,
            'match' => $match,
            'pos' => $pos,
        ];

        return $data;
    }

    /** @inheritDoc */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        // in all non-aichat modes we just output the raw content and are done
        if ($mode !== 'aichat') {
            if ($data['state'] === DOKU_LEXER_UNMATCHED) {
                $renderer->cdata($data['match']);
            }
            return true;
        }

        // we're now in aichat mode, ignore everything inside the tags
        switch ($data['state']) {
            case DOKU_LEXER_ENTER:
                $this->temporaryDoc = $renderer->doc;
                $renderer->doc = '';
                break;
            case DOKU_LEXER_EXIT:
                $renderer->doc = $this->temporaryDoc;
                $this->temporaryDoc = '';
                break;
        }

        return true;
    }
}
