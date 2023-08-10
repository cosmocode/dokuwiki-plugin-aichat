<?php

namespace dokuwiki\plugin\aichat;

class Chunk implements \JsonSerializable
{
    /** @var string */
    protected $page;
    /** @var int */
    protected $id;
    /** @var string */
    protected $text;
    /** @var float[] */
    protected $embedding;
    /** @var int */
    protected $created;
    /** @var int */
    protected $score;

    /**
     * @param string $page
     * @param int $id
     * @param string $text
     * @param float[] $embedding
     * @param int $created
     */
    public function __construct($page, $id, $text, $embedding, $created = '', $score = 0)
    {
        $this->page = $page;
        $this->id = $id;
        $this->text = $text;
        $this->embedding = $embedding;
        $this->created = $created ?: time();
        $this->score = $score;
    }

    public function __toString()
    {
        return $this->page . '#' . $this->id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param string $page
     */
    public function setPage($page): void
    {
        $this->page = $page;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text): void
    {
        $this->text = $text;
    }

    /**
     * @return float[]
     */
    public function getEmbedding()
    {
        return $this->embedding;
    }

    /**
     * @param float[] $embedding
     */
    public function setEmbedding($embedding): void
    {
        $this->embedding = $embedding;
    }

    /**
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param int $created
     */
    public function setCreated($created): void
    {
        $this->created = $created;
    }

    /**
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param int
     */
    public function setScore($score): void
    {
        $this->score = $score;
    }



    /**
     * Create a Chunk from a JSON string
     *
     * @param string $json
     * @return Chunk
     */
    static public function fromJSON($json)
    {
        $data = json_decode($json, true);
        return new self(
            $data['page'],
            $data['id'],
            $data['text'],
            $data['embedding'],
            $data['created']
        );
    }

    /** @inheritdoc */
    public function jsonSerialize()
    {
        return [
            'page' => $this->page,
            'id' => $this->id,
            'text' => $this->text,
            'embedding' => $this->embedding,
            'created' => $this->created,
        ];
    }
}
