<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\plugin\aichat\Model\ChatInterface;
use dokuwiki\plugin\aichat\Model\EmbeddingInterface;

class ModelFactory
{
    /** @var array The plugin configuration */
    protected array $config;

    public $chatModel;
    public $rephraseModel;
    public $embeddingModel;

    protected $debug = false;

    /**
     * @param array $config The plugin configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Update the configuration and reset the cached models
     *
     * @param array $config The new (partial) configuration
     */
    public function updateConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
        $this->chatModel = null;
        $this->rephraseModel = null;
        $this->embeddingModel = null;
    }

    /**
     * Set the debug flag for all models
     *
     * @param bool $debug
     */
    public function setDebug(bool $debug = true)
    {
        $this->debug = $debug;
        $this->getChatModel()->setDebug($debug);
        $this->getRephraseModel()->setDebug($debug);
        $this->getEmbeddingModel()->setDebug($debug);
    }

    /**
     * Access a cached Chat Model
     *
     * @return ChatInterface
     * @throws \Exception
     */
    public function getChatModel()
    {
        if ($this->chatModel instanceof ChatInterface) {
            return $this->chatModel;
        }
        $this->chatModel = $this->loadModel('chat', $this->config['chatmodel']);
        $this->chatModel->setDebug($this->debug);
        return $this->chatModel;
    }

    /**
     * Access a cached Rephrase Model
     *
     * @return ChatInterface
     * @throws \Exception
     */
    public function getRephraseModel()
    {
        if ($this->rephraseModel instanceof ChatInterface) {
            return $this->rephraseModel;
        }
        $this->rephraseModel = $this->loadModel('chat', $this->config['rephrasemodel']);
        $this->rephraseModel->setDebug($this->debug);
        return $this->rephraseModel;
    }

    /**
     * Access a cached Embedding Model
     *
     * @return EmbeddingInterface
     */
    public function getEmbeddingModel()
    {
        if ($this->embeddingModel instanceof EmbeddingInterface) {
            return $this->embeddingModel;
        }
        $this->embeddingModel = $this->loadModel('embedding', $this->config['embedmodel']);
        $this->embeddingModel->setDebug($this->debug);
        return $this->embeddingModel;
    }

    /**
     * Get all known models
     *
     * A (new) instance is returned for each model that is available through the current configuration.
     *
     * @param bool $availableOnly Only return models that are available
     * @param string $typeOnly Only return models of this type ('chat' or 'embedding')
     * @return array
     */
    public function getModels($availableOnly = false, $typeOnly = '')
    {
        $result = [
            'chat' => [],
            'embedding' => [],
        ];

        $jsons = glob(__DIR__ . '/Model/*/models.json');
        foreach ($jsons as $json) {
            $models = json_decode(file_get_contents($json), true);
            foreach ($models as $type => $model) {
                $namespace = basename(dirname($json));
                foreach ($model as $name => $info) {
                    try {
                        $info['instance'] = $this->loadModel($type, "$namespace $name");
                        $info['instance']->setDebug($this->debug);
                    } catch (\Exception $e) {
                        if ($availableOnly) continue;
                        $info['instance'] = false;
                    }

                    $result[$type]["$namespace $name"] = $info;
                }
            }
        }

        return $typeOnly ? $result[$typeOnly] : $result;
    }


    /**
     * Initialize a model by config name
     *
     * @param string $type 'chat' or 'embedding'
     * @param string $name The full model name including provider
     * @return ChatInterface|EmbeddingInterface
     * @throws \Exception
     */
    public function loadModel(string $type, string $name)
    {
        $type = ucfirst(strtolower($type));
        $prefix = '\\dokuwiki\\plugin\\aichat\\Model\\';
        $cname = $type . 'Model';
        $interface = $prefix . $type . 'Interface';


        [$namespace, $model] = sexplode(' ', $name, 2, '');
        $class = $prefix . $namespace . '\\' . $cname;

        if (!class_exists($class)) {
            throw new \Exception("No $cname found for $namespace", 1001);
        }

        try {
            $instance = new $class($model, $this->config);
        } catch (\Exception $e) {
            throw new \Exception("Failed to initialize $cname for $namespace: " . $e->getMessage(), 1002, $e);
        }

        if (!($instance instanceof $interface)) {
            throw new \Exception("$cname for $namespace does not implement $interface", 1003);
        }

        return $instance;
    }
}
