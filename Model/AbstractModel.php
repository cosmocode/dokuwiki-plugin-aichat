<?php

namespace dokuwiki\plugin\aichat\Model;

use dokuwiki\HTTP\DokuHTTPClient;

/**
 * Base class for all models
 *
 * Model classes also need to implement one of the following interfaces:
 * - ChatInterface
 * - EmbeddingInterface
 */
abstract class AbstractModel
{
    /** @var bool debug API communication */
    protected $debug = false;


    protected $inputTokensUsed = 0;
    protected $outputTokensUsed = 0;
    protected $tokensUsed = 0;

    /** @var int total time spent in requests by this instance */
    protected $timeUsed = 0;
    /** @var int total number of requests made by this instance */
    protected $requestsMade = 0;
    /** @var int How often to retry a request if it fails */
    public const MAX_RETRIES = 3;
    /** @var DokuHTTPClient */
    protected $http;
    /** @var int start time of the current request chain (may be multiple when retries needed) */
    protected $requestStart = 0;

    /**
     * This initializes a HTTP client
     *
     * Implementors should override this and authenticate the client.
     *
     * @param array $config The plugin configuration
     */
    public function __construct(array $config)
    {
        $this->http = new DokuHTTPClient();
        $this->http->timeout = 60;
        $this->http->headers['Content-Type'] = 'application/json';
    }

    /**
     * When enabled, the input/output of the API will be printed to STDOUT
     *
     * @param bool $debug
     */
    public function setDebug($debug = true)
    {
        $this->debug = $debug;
    }

    /**
     * This method should check the response for any errors. If the API singalled an error,
     * this method should throw an Exception with a meaningful error message.
     *
     * If the response returned any info on used tokens, they should be added to $this->tokensUsed
     *
     * The method should return the parsed response, which will be passed to the calling method.
     *
     * @param mixed $response the parsed JSON response from the API
     * @return mixed
     * @throws \Exception when the response indicates an error
     */
    abstract protected function parseAPIResponse($response);

    /**
     * Send a request to the API
     *
     * Model classes should use this method to send requests to the API.
     *
     * This method will take care of retrying and logging basic statistics.
     *
     * It is assumed that all APIs speak JSON.
     *
     * @param string $method The HTTP method to use (GET, POST, PUT, DELETE, etc.)
     * @param string $url The full URL to send the request to
     * @param array $data Payload to send, will be encoded to JSON
     * @param int $retry How often this request has been retried, do not set externally
     * @return array API response as returned by parseAPIResponse
     * @throws \Exception when anything goes wrong
     */
    protected function sendAPIRequest($method, $url, $data, $retry = 0)
    {
        // init statistics
        if ($retry === 0) {
            $this->requestStart = microtime(true);
        } else {
            sleep($retry); // wait a bit between retries
        }
        $this->requestsMade++;

        // encode payload data
        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (\JsonException $e) {
            $this->timeUsed += $this->requestStart - microtime(true);
            throw new \Exception('Failed to encode JSON for API:' . $e->getMessage(), $e->getCode(), $e);
        }

        if ($this->debug) {
            echo 'Sending ' . $method . ' request to ' . $url . ' with payload:' . "\n";
            print_r($json);
        }

        // send request and handle retries
        $this->http->sendRequest($url, $json, $method);
        $response = $this->http->resp_body;
        if ($response === false || $this->http->error) {
            if ($retry < self::MAX_RETRIES) {
                return $this->sendAPIRequest($method, $url, $data, $retry + 1);
            }
            $this->timeUsed += microtime(true) - $this->requestStart;
            throw new \Exception('API returned no response. ' . $this->http->error);
        }

        if ($this->debug) {
            echo 'Received response:' . "\n";
            print_r($response);
        }

        // decode the response
        try {
            $result = json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->timeUsed += microtime(true) - $this->requestStart;
            throw new \Exception('API returned invalid JSON: ' . $response, 0, $e);
        }

        // parse the response, retry on error
        try {
            $result = $this->parseAPIResponse($result);
        } catch (\Exception $e) {
            if ($retry < self::MAX_RETRIES) {
                return $this->sendAPIRequest($method, $url, $data, $retry + 1);
            }
            $this->timeUsed += microtime(true) - $this->requestStart;
            throw $e;
        }

        $this->timeUsed += microtime(true) - $this->requestStart;
        return $result;
    }

    /**
     * Reset the usage statistics
     *
     * Usually not needed when only handling one operation per request, but useful in CLI
     */
    public function resetUsageStats()
    {
        $this->tokensUsed = 0;
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

        $cost = 0;
        $cost += $this->inputTokensUsed * $this->getInputTokenPrice();
        if ($this instanceof ChatInterface) {
            $cost += $this->outputTokensUsed * $this->getOutputTokenPrice();
        }

        return [
            'tokens' => $this->tokensUsed + $this->inputTokensUsed + $this->outputTokensUsed,
            'cost' => round($cost / 1_000_000, 4),
            'time' => round($this->timeUsed, 2),
            'requests' => $this->requestsMade,
        ];
    }
}
