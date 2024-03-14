<?php

namespace dokuwiki\plugin\aichat\Model;

abstract class AbstractChatModel extends AbstractModel
{
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
     * Maximum size of chunks to be created for this model
     *
     * Should be a size small enough to fit at least a few chunks into the context token limit.
     *
     * @return int
     */
    abstract public function getMaxEmbeddingTokenLength();

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
}
