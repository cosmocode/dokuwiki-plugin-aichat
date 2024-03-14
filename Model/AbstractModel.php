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
     */
    abstract public function __construct($authConfig);

    /**
     * The name as used by the LLM provider
     *
     * @return string
     */
    abstract public function getModelName();

    /**
     * Get the price for 1000 tokens
     *
     * @return float
     */
    abstract public function get1kTokenPrice();

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
