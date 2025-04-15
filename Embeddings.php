<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\Extension\Event;
use dokuwiki\File\PageResolver;
use dokuwiki\plugin\aichat\Model\ChatInterface;
use dokuwiki\plugin\aichat\Model\EmbeddingInterface;
use dokuwiki\plugin\aichat\Storage\AbstractStorage;
use dokuwiki\Search\Indexer;
use splitbrain\phpcli\CLI;
use TikToken\Encoder;
use Vanderlee\Sentence\Sentence;

/**
 * Manage the embeddings index
 *
 * Pages are split into chunks of 1000 tokens each. For each chunk the embedding vector is fetched from
 * OpenAI and stored in the Storage backend.
 */
class Embeddings
{
    /** @var int maximum overlap between chunks in tokens */
    final public const MAX_OVERLAP_LEN = 200;

    /** @var ChatInterface */
    protected $chatModel;

    /** @var EmbeddingInterface */
    protected $embedModel;

    /** @var CLI|null */
    protected $logger;
    /** @var Encoder */
    protected $tokenEncoder;

    /** @var AbstractStorage */
    protected $storage;

    /** @var array remember sentences when chunking */
    private $sentenceQueue = [];

    /** @var int the time spent for the last similar chunk retrieval */
    public $timeSpent = 0;

    protected $configChunkSize;
    protected $configContextChunks;
    protected $similarityThreshold;

    /**
     * Embeddings constructor.
     *
     * @param ChatInterface $chatModel
     * @param EmbeddingInterface $embedModel
     * @param AbstractStorage $storage
     * @param array $config The plugin configuration
     */
    public function __construct(
        ChatInterface      $chatModel,
        EmbeddingInterface $embedModel,
        AbstractStorage    $storage,
                           $config
    )
    {
        $this->chatModel = $chatModel;
        $this->embedModel = $embedModel;
        $this->storage = $storage;
        $this->configChunkSize = $config['chunkSize'];
        $this->configContextChunks = $config['contextChunks'];
        $this->similarityThreshold = $config['similarityThreshold'] / 100;
    }

    /**
     * Access storage
     *
     * @return AbstractStorage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Override the number of used context chunks
     *
     * @param int $max
     * @return void
     */
    public function setConfigContextChunks(int $max)
    {
        if ($max <= 0) throw new \InvalidArgumentException('max context chunks must be greater than 0');
        $this->configContextChunks = $max;
    }

    /**
     * Override the similiarity threshold
     *
     * @param float $threshold
     * @return void
     */
    public function setSimilarityThreshold(float $threshold)
    {
        if ($threshold < 0 || $threshold > 1) throw new \InvalidArgumentException('threshold must be between 0 and 1');
        $this->similarityThreshold = $threshold;
    }

    /**
     * Add a logger instance
     *
     * @return void
     */
    public function setLogger(CLI $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the token encoder instance
     *
     * @return Encoder
     */
    public function getTokenEncoder()
    {
        if (!$this->tokenEncoder instanceof Encoder) {
            $this->tokenEncoder = new Encoder();
        }
        return $this->tokenEncoder;
    }

    /**
     * Return the chunk size to use
     *
     * @return int
     */
    public function getChunkSize()
    {
        $tokenlimit = $this->chatModel->getMaxInputTokenLength();
        if(!$tokenlimit) {
            // no token limit, use the configured chunk size
            return $this->configChunkSize;
        }

        return min(
            floor($this->chatModel->getMaxInputTokenLength() / 4), // be able to fit 4 chunks into the max input
            floor($this->embedModel->getMaxInputTokenLength() * 0.9), // only use 90% of the embedding model to be safe
            $this->configChunkSize, // this is usually the smallest
        );
    }

    /**
     * Update the embeddings storage
     *
     * @param string $skipRE Regular expression to filter out pages (full RE with delimiters)
     * @param string $matchRE Regular expression pages have to match to be included (full RE with delimiters)
     * @param bool $clear Should any existing storage be cleared before updating?
     * @return void
     * @throws \Exception
     */
    public function createNewIndex($skipRE = '', $matchRE = '', $clear = false)
    {
        $indexer = new Indexer();
        $pages = $indexer->getPages();

        $this->storage->startCreation($clear);
        foreach ($pages as $pid => $page) {
            $chunkID = $pid * 100; // chunk IDs start at page ID * 100

            if (
                !page_exists($page) ||
                isHiddenPage($page) ||
                filesize(wikiFN($page)) < 150 || // skip very small pages
                ($skipRE && preg_match($skipRE, (string)$page)) ||
                ($matchRE && !preg_match($matchRE, ":$page"))
            ) {
                // this page should not be in the index (anymore)
                $this->storage->deletePageChunks($page, $chunkID);
                continue;
            }

            $firstChunk = $this->storage->getChunk($chunkID);
            if ($firstChunk && @filemtime(wikiFN($page)) < $firstChunk->getCreated()) {
                // page is older than the chunks we have, reuse the existing chunks
                $this->storage->reusePageChunks($page, $chunkID);
                if ($this->logger instanceof CLI) $this->logger->info("Reusing chunks for $page");
            } else {
                // page is newer than the chunks we have, create new chunks
                $this->storage->deletePageChunks($page, $chunkID);
                $chunks = $this->createPageChunks($page, $chunkID);
                if ($chunks) $this->storage->addPageChunks($chunks);
            }
        }
        $this->storage->finalizeCreation();
    }

    /**
     * Split the given page, fetch embedding vectors and return Chunks
     *
     * Will use the text renderer plugin if available to get the rendered text.
     * Otherwise the raw wiki text is used.
     *
     * @param string $page Name of the page to split
     * @param int $firstChunkID The ID of the first chunk of this page
     * @return Chunk[] A list of chunks created for this page
     * @emits INDEXER_PAGE_ADD support plugins that add additional data to the page
     * @throws \Exception
     */
    public function createPageChunks($page, $firstChunkID)
    {
        $chunkList = [];

        global $ID;
        $ID = $page;
        try {
            $text = p_cached_output(wikiFN($page), 'aichat', $page);
        } catch (\Throwable $e) {
            if ($this->logger) $this->logger->error(
                'Failed to render page {page}. Using raw text instead. {msg}',
                ['page' => $page, 'msg' => $e->getMessage()]
            );
            $text = rawWiki($page);
        }

        $crumbs = $this->breadcrumbTrail($page);

        // allow plugins to modify the text before splitting
        $eventData = [
            'page' => $page,
            'body' => '',
            'metadata' => ['title' => $page, 'relation_references' => []],
        ];
        $event = new Event('INDEXER_PAGE_ADD', $eventData);
        if ($event->advise_before()) {
            $text = $eventData['body'] . ' ' . $text;
        } else {
            $text = $eventData['body'];
        }

        $parts = $this->splitIntoChunks($text);
        foreach ($parts as $part) {
            if (trim((string)$part) == '') continue; // skip empty chunks

            $part = $crumbs . "\n\n" . $part; // add breadcrumbs to each chunk

            try {
                $embedding = $this->embedModel->getEmbedding($part);
            } catch (\Exception $e) {
                if ($this->logger instanceof CLI) {
                    $this->logger->error(
                        'Failed to get embedding for chunk of page {page}: {msg}',
                        ['page' => $page, 'msg' => $e->getMessage()]
                    );
                }
                continue;
            }
            $chunkList[] = new Chunk($page, $firstChunkID, $part, $embedding);
            $firstChunkID++;
        }
        if ($this->logger instanceof CLI) {
            if ($chunkList !== []) {
                $this->logger->success(
                    '{id} split into {count} chunks',
                    ['id' => $page, 'count' => count($chunkList)]
                );
            } else {
                $this->logger->warning('{id} could not be split into chunks', ['id' => $page]);
            }
        }
        return $chunkList;
    }

    /**
     * Do a nearest neighbor search for chunks similar to the given question
     *
     * Returns only chunks the current user is allowed to read, may return an empty result.
     * The number of returned chunks depends on the MAX_CONTEXT_LEN setting.
     *
     * @param string $query The question
     * @param string $lang Limit results to this language
     * @param bool $limits Apply chat token limits to the number of chunks returned?
     * @return Chunk[]
     * @throws \Exception
     */
    public function getSimilarChunks($query, $lang = '', $limits = true)
    {
        global $auth;
        $vector = $this->embedModel->getEmbedding($query);

        $tokenlimit = $limits ? $this->chatModel->getMaxInputTokenLength() : 0;

        if ($tokenlimit) {
            $fetch = min(
                ($tokenlimit / $this->getChunkSize()),
                $this->configContextChunks
            );
        } else {
            $fetch = $this->configContextChunks;
        }

        $time = microtime(true);
        $chunks = $this->storage->getSimilarChunks($vector, $lang, $fetch);
        $this->timeSpent = round(microtime(true) - $time, 2);
        if ($this->logger instanceof CLI) {
            $this->logger->info(
                'Fetched {count} similar chunks from store in {time} seconds. Query: {query}',
                ['count' => count($chunks), 'time' => $this->timeSpent, 'query' => $query]
            );
        }

        $size = 0;
        $result = [];
        foreach ($chunks as $chunk) {
            // filter out chunks the user is not allowed to read
            if ($auth && auth_quickaclcheck($chunk->getPage()) < AUTH_READ) continue;
            if ($chunk->getScore() < $this->similarityThreshold) continue;

            if ($tokenlimit) {
                $chunkSize = count($this->getTokenEncoder()->encode($chunk->getText()));
                if ($size + $chunkSize > $tokenlimit) break; // we have enough
            }

            $result[] = $chunk;
            $size += $chunkSize ?? 0;

            if (count($result) >= $this->configContextChunks) break; // we have enough
        }
        return $result;
    }

    /**
     * Returns all chunks for a page
     *
     * Does not apply configContextChunks but checks token limits if requested
     *
     * @param string $page
     * @param bool $limits Apply chat token limits to the number of chunks returned?
     * @return Chunk[]
     */
    public function getPageChunks($page, $limits = true)
    {
        global $auth;
        if ($auth && auth_quickaclcheck($page) < AUTH_READ) {
            if ($this->logger instanceof CLI) $this->logger->warning(
                'User not allowed to read context page {page}', ['page' => $page]
            );
            return [];
        }

        $indexer = new Indexer();
        $pages = $indexer->getPages();
        $pos = array_search(cleanID($page), $pages);

        if ($pos === false) {
            if ($this->logger instanceof CLI) $this->logger->warning(
                'Context page {page} is not in index', ['page' => $page]
            );
            return [];
        }

        $chunks = $this->storage->getPageChunks($page, $pos * 100);

        $tokenlimit = $limits ? $this->chatModel->getMaxInputTokenLength() : 0;

        $size = 0;
        $result = [];
        foreach ($chunks as $chunk) {
            if ($tokenlimit) {
                $chunkSize = count($this->getTokenEncoder()->encode($chunk->getText()));
                if ($size + $chunkSize > $tokenlimit) break; // we have enough
            }

            $result[] = $chunk;
            $size += $chunkSize ?? 0;
        }

        return $result;
    }


    /**
     * Create a breadcrumb trail for the given page
     *
     * Uses the first heading of each namespace and the page itself. This is added as a prefix to
     * each chunk to give the AI some context.
     *
     * @param string $id
     * @return string
     */
    protected function breadcrumbTrail($id)
    {
        $namespaces = explode(':', getNS($id));
        $resolver = new PageResolver($id);
        $crumbs = [];

        // all namespaces
        $check = '';
        foreach ($namespaces as $namespace) {
            $check .= $namespace . ':';
            $page = $resolver->resolveId($check);
            $title = p_get_first_heading($page);
            $crumbs[] = $title ? "$title ($namespace)" : $namespace;
        }

        // the page itself
        $title = p_get_first_heading($id);
        $page = noNS($id);
        $crumbs[] = $title ? "$title ($page)" : $page;

        return implode(' » ', $crumbs);
    }

    /**
     * @param $text
     * @return array
     * @throws \Exception
     * @todo support splitting too long sentences
     */
    protected function splitIntoChunks($text)
    {
        $sentenceSplitter = new Sentence();
        $tiktok = $this->getTokenEncoder();

        $chunks = [];
        $sentences = $sentenceSplitter->split($text);

        $chunklen = 0;
        $chunk = '';
        while ($sentence = array_shift($sentences)) {
            $slen = count($tiktok->encode($sentence));
            if ($slen > $this->getChunkSize()) {
                // sentence is too long, we need to split it further
                if ($this->logger instanceof CLI) $this->logger->warning(
                    'Sentence too long, splitting not implemented yet'
                );
                continue;
            }

            if ($chunklen + $slen < $this->getChunkSize()) {
                // add to current chunk
                $chunk .= $sentence;
                $chunklen += $slen;
                // remember sentence for overlap check
                $this->rememberSentence($sentence);
            } else {
                // add current chunk to result
                $chunk = trim($chunk);
                if ($chunk !== '') $chunks[] = $chunk;

                // start new chunk with remembered sentences
                $chunk = implode(' ', $this->sentenceQueue);
                $chunk .= $sentence;
                $chunklen = count($tiktok->encode($chunk));
            }
        }
        $chunks[] = $chunk;

        return $chunks;
    }

    /**
     * Add a sentence to the queue of remembered sentences
     *
     * @param string $sentence
     * @return void
     */
    protected function rememberSentence($sentence)
    {
        // add sentence to queue
        $this->sentenceQueue[] = $sentence;

        // remove oldest sentences from queue until we are below the max overlap
        $encoder = $this->getTokenEncoder();
        while (count($encoder->encode(implode(' ', $this->sentenceQueue))) > self::MAX_OVERLAP_LEN) {
            array_shift($this->sentenceQueue);
        }
    }
}
