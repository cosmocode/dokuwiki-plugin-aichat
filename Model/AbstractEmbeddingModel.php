<?php

namespace dokuwiki\plugin\aichat\Model;

abstract class AbstractEmbeddingModel extends AbstractModel
{
    /**
     * Maximum size of chunks this model could handle
     *
     * Generally the maximum is defined by the same method in the ChatModel because chunks
     * need to fit into the chat request.
     *
     * @return int
     */
    abstract public function getMaxEmbeddingTokenLength();

    /**
     * Get the dimensions of the embedding vectors
     *
     * @return int
     */
    abstract public function getDimensions();

    /**
     * Get the embedding vectors for a given text
     *
     * @param string $text
     * @return float[]
     * @throws \Exception
     */
    abstract public function getEmbedding($text);
}
