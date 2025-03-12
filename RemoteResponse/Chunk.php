<?php

namespace dokuwiki\plugin\aichat\RemoteResponse;

use dokuwiki\plugin\aichat\Chunk as BaseChunk;
use dokuwiki\Remote\Response\ApiResponse;

class Chunk extends ApiResponse
{
    /** @var string The page id of the source */
    public $page;
    /** @var string The title of the source page */
    public $title;
    /** @var string The chunk id of the source (pages are split into chunks) */
    public $id;
    /** @var float The similarity score of this source to the query (between 0 and 1) */
    public $score;
    /** @var string The language of the source */
    public $lang;

    public function __construct(BaseChunk $originalChunk)
    {
        $this->page = $originalChunk->getPage();
        $this->id = $originalChunk->getId();
        $this->score = $originalChunk->getScore();
        $this->lang = $originalChunk->getLanguage();
        $this->title = p_get_first_heading($this->page);
    }

    public function __toString()
    {
        return $this->page . '--' . $this->id;
    }
}
