<?php

use dokuwiki\plugin\aichat\backend\Chunk;
use splitbrain\phpcli\Colors;
use splitbrain\phpcli\Options;


/**
 * DokuWiki Plugin aichat (CLI Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class cli_plugin_aichat extends \dokuwiki\Extension\CLIPlugin
{
    /** @var helper_plugin_aichat */
    protected $helper;

    public function __construct($autocatch = true)
    {
        parent::__construct($autocatch);
        $this->helper = plugin_load('helper', 'aichat');
        $this->helper->getEmbeddings()->setLogger($this);
    }


    /** @inheritDoc */
    protected function setup(Options $options)
    {
        $options->useCompactHelp();

        $options->setHelp('Manage and query the AI chatbot data');

        $options->registerCommand('embed', 'Create embeddings for all pages');

        $options->registerCommand('similar', 'Search for similar pages');
        $options->registerArgument('query', 'Look up chunks similar to this query', true, 'similar');

        $options->registerCommand('ask', 'Ask a question');
        $options->registerArgument('question', 'The question to ask', true, 'ask');

        $options->registerCommand('chat', 'Start an interactive chat session');

        $options->registerCommand('split', 'Split a page into chunks (for debugging)');
        $options->registerArgument('page', 'The page to split', true, 'split');

        $options->registerCommand('info', 'Get Info about the vector storage');
    }

    /** @inheritDoc */
    protected function main(Options $options)
    {
        switch ($options->getCmd()) {

            case 'embed':
                $this->createEmbeddings();
                break;
            case 'similar':
                $this->similar($options->getArgs()[0]);
                break;
            case 'ask':
                $this->ask($options->getArgs()[0]);
                break;
            case 'chat':
                $this->chat();
                break;
            case 'split':
                $this->split($options->getArgs()[0]);
                break;
            case 'info':
                $this->treeinfo();
                break;
            default:
                echo $options->help();
        }
    }

    /**
     * @return void
     */
    protected function treeinfo()
    {
        $stats = $this->helper->getEmbeddings()->getStorage()->statistics();
        foreach($stats as $key => $value) {
            echo $key . ': ' . $value. "\n";
        }
    }

    /**
     * Split the given page into chunks and print them
     *
     * @param string $page
     * @return void
     * @throws Exception
     */
    protected function split($page)
    {
        $text = rawWiki($page);
        $chunks = $this->helper->getEmbeddings()->splitIntoChunks($text);
        foreach ($chunks as $chunk) {
            echo $chunk;
            echo "\n";
            $this->colors->ptln('--------------------------------', Colors::C_LIGHTPURPLE);
        }
        $this->success('Split into ' . count($chunks) . ' chunks');
    }

    /**
     * Interactive Chat Session
     *
     * @return void
     * @throws Exception
     */
    protected function chat()
    {
        $history = [];
        while ($q = $this->readLine('Your Question')) {
            if ($history) {
                $question = $this->helper->rephraseChatQuestion($q, $history);
                $this->colors->ptln("Interpretation: $question", Colors::C_LIGHTPURPLE);
            } else {
                $question = $q;
            }
            $result = $this->helper->askQuestion($question);
            $history[] = [$q, $result['answer']];
            $this->printAnswer($result);
        }
    }

    /**
     * Print the given detailed answer in a nice way
     *
     * @param array $answer
     * @return void
     */
    protected function printAnswer($answer)
    {
        $this->colors->ptln($answer['answer'], Colors::C_LIGHTCYAN);
        echo "\n";
        foreach ($answer['sources'] as $source) {
            /** @var Chunk $source */
            $this->colors->ptln("\t" . $source->getPage(), Colors::C_LIGHTBLUE);
        }
        echo "\n";
    }

    /**
     * Handle a single, standalone question
     *
     * @param string $query
     * @return void
     * @throws Exception
     */
    protected function ask($query)
    {
        $result = $this->helper->askQuestion($query);
        $this->printAnswer($result);
    }

    /**
     * Get the pages that are similar to the query
     *
     * @param string $query
     * @return void
     */
    protected function similar($query)
    {
        $sources = $this->helper->getEmbeddings()->getSimilarChunks($query);
        foreach ($sources as $source) {
            $this->colors->ptln($source->getPage(), Colors::C_LIGHTBLUE);
        }
    }

    /**
     * Recreate chunks and embeddings for all pages
     *
     * @return void
     * @todo make skip regex configurable
     */
    protected function createEmbeddings()
    {
        ini_set('memory_limit', -1); // we may need a lot of memory here
        $this->helper->getEmbeddings()->createNewIndex('/(^|:)(playground|sandbox)(:|$)/');
        $this->notice('Peak memory used: {memory}', ['memory' => filesize_h(memory_get_peak_usage(true))]);
    }

    /**
     * Interactively ask for a value from the user
     *
     * @param string $prompt
     * @return string
     */
    protected function readLine($prompt)
    {
        $value = '';

        while ($value === '') {
            echo $prompt;
            echo ': ';

            $fh = fopen('php://stdin', 'r');
            $value = trim(fgets($fh));
            fclose($fh);
        }

        return $value;
    }
}

