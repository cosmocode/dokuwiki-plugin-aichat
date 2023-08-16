<?php

use dokuwiki\plugin\aichat\Chunk;
use dokuwiki\Search\Indexer;

/**
 * DokuWiki Plugin aichat (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class syntax_plugin_aichat_similar extends \dokuwiki\Extension\SyntaxPlugin
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
        $this->Lexer->addSpecialPattern('~~similar~~', $mode, 'plugin_aichat_similar');
    }


    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return [];
    }

    /** @inheritDoc */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        global $INFO;

        /** @var helper_plugin_aichat $helper */
        $helper = $this->loadHelper('aichat');

        $id = $INFO['id'];

        $pages = (new Indexer())->getPages();
        $pos = array_search($id, $pages);
        if($pos === false) return true;

        $storage = $helper->getStorage();
        $chunks = $storage->getPageChunks($id, $pos*100);
        $similar = [];
        foreach ($chunks as $chunk) {
            $similar += $storage->getSimilarChunks($chunk->getEmbedding(), 10);
        }
        $similar = array_unique($similar);
        $similar = array_filter($similar, function ($chunk) use ($id) {
            return $chunk->getPage() !== $id;
        });
        usort($similar, function ($a, $b) {
            /** @var Chunk $a */
            /** @var Chunk $b */
            return $b->getScore() <=> $a->getScore();
        });

        if(!$similar) return true;

        $similar = array_slice($similar, 0, 5);

        $renderer->listu_open();
        foreach ($similar as $chunk) {
            /** @var Chunk $chunk */
            $renderer->listitem_open(1);
            $renderer->listcontent_open();
            $renderer->internallink($chunk->getPage(), null, null, false, 'navigation');
            $renderer->listcontent_close();
            $renderer->listitem_close();
        }
        $renderer->listu_close();

        return true;
    }
}

