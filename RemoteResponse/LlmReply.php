<?php

namespace dokuwiki\plugin\aichat\RemoteResponse;

use dokuwiki\plugin\aichat\RemoteResponse\Chunk as ChunkResponse;
use dokuwiki\Remote\Response\ApiResponse;

class LlmReply extends ApiResponse
{
    /** @var string The question as asked */
    public $question;
    /** @var string The answer provided by the LLM */
    public $answer;
    /** @var ChunkResponse[] The sources provided to the model to answer the questions */
    public $sources = [];

    public function __construct($data)
    {
        $this->question = $data['question'];
        $this->answer = $data['answer'];

        foreach ($data['sources'] as $source) {
            $this->sources[] = new ChunkResponse($source);
        }
    }

    public function __toString()
    {
        return $this->question;
    }
}
