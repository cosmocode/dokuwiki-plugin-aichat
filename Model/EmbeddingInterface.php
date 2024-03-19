<?php

namespace dokuwiki\plugin\aichat\Model;

/**
 * Defines an embedding model
 */
interface EmbeddingInterface extends ModelInterface
{
    /**
     * Get the dimensions of the embedding vectors
     */
    public function getDimensions(): int;

    /**
     * Get the embedding vectors for a given text
     *
     * @param string $text
     * @return float[]
     * @throws \Exception
     */
    public function getEmbedding($text): array;
}
