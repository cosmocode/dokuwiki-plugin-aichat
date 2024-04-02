<?php

namespace dokuwiki\plugin\aichat\Model;

/**
 * Defines a chat completion model
 */
interface ChatInterface extends ModelInterface
{
    /**
     * Maximum number of tokens the model can output as an answer
     */
    public function getMaxOutputTokenLength(): int;

    /**
     * The price for 1,000,000 output tokens in USD
     */
    public function getOutputTokenPrice(): float;

    /**
     * Answer a given question.
     *
     * Any prompt, chat history, context etc. will already be included in the $messages array.
     *
     * @param array $messages Messages in OpenAI format (with role and content)
     * @return string The answer
     * @throws \Exception
     */
    public function getAnswer(array $messages): string;
}
