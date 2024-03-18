<?php

namespace dokuwiki\plugin\aichat\Model;

/**
 * Defines an embedding model
 */
interface EmbeddingInterface extends ModelInterface
{
    /**
     * Maximum size of chunks this model could handle
     *
     * Generally the maximum is defined by the same method in the ChatModel because chunks
     * need to fit into the chat request.
     *
     * @return int
     */
    public function getMaxEmbeddingTokenLength();

    /**
     * Get the dimensions of the embedding vectors
     *
     * @return int
     */
    public function getDimensions();

    /**
     * Get the embedding vectors for a given text
     *
     * @param string $text
     * @return float[]
     * @throws \Exception
     */
    public function getEmbedding($text);
}
