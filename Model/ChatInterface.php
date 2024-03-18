<?php

namespace dokuwiki\plugin\aichat\Model;

/**
 * Defines a chat completion model
 */
interface ChatInterface extends ModelInterface
{
    /**
     * Maximum number of tokens to use when creating context info. Should be smaller than the absolute
     * token limit of the model, so that prompts and questions can be added.
     *
     * @return int
     */
    public function getMaxContextTokenLength();

    /**
     * Maximum number of tokens to use as context when rephrasing a question. Should be smaller than the
     * absolute token limit of the model, so that prompts and questions can be added.
     *
     * @return int
     */
    public function getMaxRephrasingTokenLength();

    /**
     * Maximum size of chunks to be created for this model
     *
     * Should be a size small enough to fit at least a few chunks into the context token limit.
     *
     * @return int
     */
    public function getMaxEmbeddingTokenLength();

    /**
     * Answer a given question.
     *
     * Any prompt, chat history, context etc. will already be included in the $messages array.
     *
     * @param array $messages Messages in OpenAI format (with role and content)
     * @return string The answer
     * @throws \Exception
     */
    public function getAnswer($messages);
}
