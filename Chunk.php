<?php

namespace dokuwiki\plugin\aichat;

class Chunk implements \JsonSerializable, \Stringable
{
    /** @var int */
    protected $created;
    /** @var string */
    protected $language;

    /**
     * @param string $page
     * @param int $id
     * @param string $text
     * @param float[] $embedding
     * @param int $created
     * @param int $score
     */
    public function __construct(
        protected $page,
        protected $id,
        protected $text,
        protected $embedding,
                  $lang = '',
                  $created = '',
        protected $score = 0
    )
    {
        $this->language = $lang ?: $this->determineLanguage();
        $this->created = $created ?: time();
    }

    public function __toString(): string
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

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language): void
    {
        $this->language = $language;
    }

    /**
     * Initialize the language of the chunk
     *
     * When the translation plugin is available it is used to determine the language, otherwise the default language
     * is used.
     *
     * @return string The lanuaage code
     */
    protected function determineLanguage()
    {
        global $conf;
        /** @var \helper_plugin_translation $trans */
        $trans = plugin_load('helper', 'translation');
        if ($trans) {
            $lc = $trans->realLC($trans->getLangPart($this->page));
        } else {
            $lc = $conf['lang'];
        }
        return $lc;
    }


    /**
     * Create a Chunk from a JSON string
     *
     * @param string $json
     * @return Chunk
     */
    public static function fromJSON($json)
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return new self(
            $data['page'],
            $data['id'],
            $data['text'],
            $data['embedding'],
            $data['language'] ?? '',
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
            'language' => $this->language,
            'created' => $this->created,
        ];
    }
}
