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
     * The name as used by the LLM provider
     *
     * @return string
     */
    public function getModelName();

    /**
     * Get the price for 1,000,000 tokens
     *
     * @return float
     */
    public function get1MillionTokenPrice();


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
}
