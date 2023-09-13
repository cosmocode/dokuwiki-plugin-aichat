<?php

namespace dokuwiki\plugin\aichat\Model;

abstract class AbstractModel
{
    /** @var int total tokens used by this instance */
    protected $tokensUsed = 0;
    /** @var int total cost used by this instance (multiplied by 1000*10000) */
    protected $costEstimate = 0;
    /** @var int total time spent in requests by this instance */
    protected $timeUsed = 0;
    /** @var int total number of requests made by this instance */
    protected $requestsMade = 0;


    /**
     * @param array $authConfig Any configuration this Model/Service may need to authenticate
     * @throws \Exception
     */
    abstract public function __construct($authConfig);

    /**
     * Maximum size of chunks this model can handle
     *
     * @return int
     */
    abstract public function getMaxEmbeddingTokenLength();

    /**
     * Maximum number of tokens to use when creating context info. Should be smaller than the absolute
     * token limit of the model, so that prompts and questions can be added.
     *
     * @return int
     */
    abstract public function getMaxContextTokenLength();

    /**
     * Maximum number of tokens to use as context when rephrasing a question. Should be smaller than the
     * absolute token limit of the model, so that prompts and questions can be added.
     *
     * @return int
     */
    public function getMaxRephrasingTokenLength()
    {
        return $this->getMaxContextTokenLength();
    }

    /**
     * Get the embedding vectors for a given text
     *
     * @param string $text
     * @return float[]
     * @throws \Exception
     */
    abstract public function getEmbedding($text);

    /**
     * Answer a given question.
     *
     * Any prompt, chat history, context etc. will already be included in the $messages array.
     *
     * @param array $messages Messages in OpenAI format (with role and content)
     * @return string The answer
     * @throws \Exception
     */
    abstract public function getAnswer($messages);

    /**
     * This is called to let the LLM rephrase a question using given context
     *
     * Any prompt, chat history, context etc. will already be included in the $messages array.
     * This calls getAnswer() by default, but you may want to use a different model instead.
     *
     * @param array $messages Messages in OpenAI format (with role and content)
     * @return string The new question
     * @throws \Exception
     */
    public function getRephrasedQuestion($messages)
    {
        return $this->getAnswer($messages);
    }

    /**
     * Reset the usage statistics
     *
     * Usually not needed when only handling one operation per request, but useful in CLI
     */
    public function resetUsageStats()
    {
        $this->tokensUsed = 0;
        $this->costEstimate = 0;
        $this->timeUsed = 0;
        $this->requestsMade = 0;
    }

    /**
     * Get the usage statistics for this instance
     *
     * @return string[]
     */
    public function getUsageStats()
    {
        return [
            'tokens' => $this->tokensUsed,
            'cost' => round($this->costEstimate / 1000 / 10000, 4),
            'time' => round($this->timeUsed, 2),
            'requests' => $this->requestsMade,
        ];
    }
}
