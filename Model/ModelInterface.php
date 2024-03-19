<?php

namespace dokuwiki\plugin\aichat\Model;

/**
 * Interface for all models
 *
 * Model classes should inherit from AbstractModel, to avoid handling the statistics themselves.
 */
interface ModelInterface
{
    /**
     * Initialize the model
     *
     * @param string $name The name of the model as used by the LLM provider
     * @param array $config The plugin configuration
     * @throws \Exception when the model cannot be initialized
     */
    public function __construct(string $name, array $config);

    /**
     * The name as used by the LLM provider
     *
     * @return string
     */
    public function getModelName();

    /**
     * Reset the usage statistics
     *
     * Usually not needed when only handling one operation per request, but useful in CLI
     */
    public function resetUsageStats();

    /**
     * Get the usage statistics for this instance
     *
     * @return string[]
     */
    public function getUsageStats();

    /**
     * Maximum number of tokens the model can handle as input.
     *
     * This is the absolute limit, including any context, prompts, questions etc.
     */
    public function getMaxInputTokenLength(): int;

    /**
     * The price for 1,000,000 input tokens in USD
     */
    public function getInputTokenPrice(): float;

}
