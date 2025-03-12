<?php

namespace dokuwiki\plugin\aichat\Model;

use dokuwiki\HTTP\DokuHTTPClient;

/**
 * Base class for all models
 *
 * Model classes also need to implement one of the following interfaces:
 * - ChatInterface
 * - EmbeddingInterface
 *
 * This class already implements most of the requirements for these interfaces.
 *
 * In addition to any missing interface methods, model implementations will need to
 * extend the constructor to handle the plugin configuration and implement the
 * parseAPIResponse() method to handle the specific API response.
 */
abstract class AbstractModel implements ModelInterface
{
    /** @var string The model name */
    protected $modelName;
    /** @var string The full model name */
    protected $modelFullName;
    /** @var array The model info from the model.json file */
    protected $modelInfo;

    /** @var int input tokens used since last reset */
    protected $inputTokensUsed = 0;
    /** @var int output tokens used since last reset */
    protected $outputTokensUsed = 0;
    /** @var int total time spent in requests since last reset */
    protected $timeUsed = 0;
    /** @var int total number of requests made since last reset */
    protected $requestsMade = 0;
    /** @var int start time of the current request chain (may be multiple when retries needed) */
    protected $requestStart = 0;

    /** @var int How often to retry a request if it fails */
    public const MAX_RETRIES = 3;

    /** @var DokuHTTPClient */
    protected $http;
    /** @var bool debug API communication */
    protected $debug = false;

    // region ModelInterface

    /** @inheritdoc */
    public function __construct(string $name, array $config)
    {
        $this->modelName = $name;
        $this->http = new DokuHTTPClient();
        $this->http->timeout = 60;
        $this->http->headers['Content-Type'] = 'application/json';
        $this->http->headers['Accept'] = 'application/json';

        $reflect = new \ReflectionClass($this);
        $json = dirname($reflect->getFileName()) . '/models.json';
        if (!file_exists($json)) {
            throw new \Exception('Model info file not found at ' . $json, 2001);
        }
        try {
            $modelinfos = json_decode(file_get_contents($json), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \Exception('Failed to parse model info file: ' . $e->getMessage(), 2002, $e);
        }

        $this->modelFullName = basename(dirname($reflect->getFileName()) . ' ' . $name);

        if ($this instanceof ChatInterface) {
            if (isset($modelinfos['chat'][$name])) {
                $this->modelInfo = $modelinfos['chat'][$name];
            } else {
                $this->modelInfo = $this->loadUnknownModelInfo();
            }

        }

        if ($this instanceof EmbeddingInterface) {
            if (isset($modelinfos['embedding'][$name])) {
                $this->modelInfo = $modelinfos['embedding'][$name];
            } else {
                $this->modelInfo = $this->loadUnknownModelInfo();
            }
        }
    }

    /** @inheritdoc */
    public function __toString(): string
    {
        return $this->modelFullName;
    }


    /** @inheritdoc */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * Reset the usage statistics
     *
     * Usually not needed when only handling one operation per request, but useful in CLI
     */
    public function resetUsageStats()
    {
        $this->inputTokensUsed = 0;
        $this->outputTokensUsed = 0;
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
            'tokens' => $this->inputTokensUsed + $this->outputTokensUsed,
            'cost' => sprintf("%.6f", $cost / 1_000_000),
            'time' => round($this->timeUsed, 2),
            'requests' => $this->requestsMade,
        ];
    }

    /** @inheritdoc */
    public function getMaxInputTokenLength(): int
    {
        return $this->modelInfo['inputTokens'];
    }

    /** @inheritdoc */
    public function getInputTokenPrice(): float
    {
        return $this->modelInfo['inputTokenPrice'];
    }

    /** @inheritdoc */
    function loadUnknownModelInfo(): array
    {
        $info = [
            'description' => $this->modelFullName,
            'inputTokens' => 1024,
            'inputTokenPrice' => 0,
        ];

        if ($this instanceof ChatInterface) {
            $info['outputTokens'] = 1024;
            $info['outputTokenPrice'] = 0;
        } elseif ($this instanceof EmbeddingInterface) {
            $info['dimensions'] = 512;
        }

        return $info;
    }

    // endregion

    // region EmbeddingInterface

    /** @inheritdoc */
    public function getDimensions(): int
    {
        return $this->modelInfo['dimensions'];
    }

    // endregion

    // region ChatInterface

    public function getMaxOutputTokenLength(): int
    {
        return $this->modelInfo['outputTokens'];
    }

    public function getOutputTokenPrice(): float
    {
        return $this->modelInfo['outputTokenPrice'];
    }

    // endregion

    // region API communication

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
     * @param array|string $data Payload to send, will be encoded to JSON
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
            throw new \Exception('Failed to encode JSON for API:' . $e->getMessage(), 2003, $e);
        }

        if ($this->debug) {
            echo 'Sending ' . $method . ' request to ' . $url . ' with payload:' . "\n";
            print_r($json);
            echo "\n";
        }

        // send request and handle retries
        $this->http->sendRequest($url, $json, $method);
        $response = $this->http->resp_body;
        if ($response === false || $this->http->error) {
            if ($retry < self::MAX_RETRIES) {
                return $this->sendAPIRequest($method, $url, $data, $retry + 1);
            }
            $this->timeUsed += microtime(true) - $this->requestStart;
            throw new \Exception('API returned no response. ' . $this->http->error, 2004);
        }

        if ($this->debug) {
            echo 'Received response:' . "\n";
            print_r($response);
            echo "\n";
        }

        // decode the response
        try {
            $result = json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->timeUsed += microtime(true) - $this->requestStart;
            throw new \Exception('API returned invalid JSON: ' . $response, 2005, $e);
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

    // endregion
}
