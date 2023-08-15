<?php

use dokuwiki\Extension\CLIPlugin;
use dokuwiki\plugin\aichat\Chunk;
use dokuwiki\Search\Indexer;
use splitbrain\phpcli\Colors;
use splitbrain\phpcli\Options;
use splitbrain\phpcli\TableFormatter;


/**
 * DokuWiki Plugin aichat (CLI Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class cli_plugin_aichat extends CLIPlugin
{
    /** @var helper_plugin_aichat */
    protected $helper;

    public function __construct($autocatch = true)
    {
        parent::__construct($autocatch);
        $this->helper = plugin_load('helper', 'aichat');
        $this->helper->setLogger($this);
    }

    /** @inheritDoc */
    protected function setup(Options $options)
    {
        $options->useCompactHelp();

        $options->setHelp(
            'Manage and query the AI chatbot data. Please note that calls to your LLM provider will be made. ' .
            'This may incur costs.'
        );

        $options->registerCommand(
            'embed',
            'Create embeddings for all pages. This skips pages that already have embeddings'
        );
        $options->registerOption(
            'clear',
            'Clear all existing embeddings before creating new ones',
            'c', false, 'embed'
        );

        $options->registerCommand('maintenance', 'Run storage maintenance. Refert to the documentation for details.');

        $options->registerCommand('similar', 'Search for similar pages');
        $options->registerArgument('query', 'Look up chunks similar to this query', true, 'similar');

        $options->registerCommand('ask', 'Ask a question');
        $options->registerArgument('question', 'The question to ask', true, 'ask');

        $options->registerCommand('chat', 'Start an interactive chat session');

        $options->registerCommand('split', 'Split a page into chunks (for debugging)');
        $options->registerArgument('page', 'The page to split', true, 'split');

        $options->registerCommand('page', 'Check if chunks for a given page are available (for debugging)');
        $options->registerArgument('page', 'The page to check', true, 'page');

        $options->registerCommand('info', 'Get Info about the vector storage');
    }

    /** @inheritDoc */
    protected function main(Options $options)
    {
        ini_set('memory_limit', -1);
        switch ($options->getCmd()) {

            case 'embed':
                $this->createEmbeddings($options->getOpt('clear'));
                break;
            case 'maintenance':
                $this->runMaintenance();
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
            case 'page':
                $this->page($options->getArgs()[0]);
                break;
            case 'info':
                $this->showinfo();
                break;
            default:
                echo $options->help();
        }
    }

    /**
     * @return void
     */
    protected function showinfo()
    {

        $stats = [
            'model' => $this->getConf('model'),
        ];
        $stats = array_merge($stats, $this->helper->getStorage()->statistics());
        $this->printTable($stats);
    }

    /**
     * Print key value data as tabular data
     *
     * @param array $data
     * @param int $level
     * @return void
     */
    protected function printTable($data, $level = 0)
    {
        $tf = new TableFormatter($this->colors);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                echo $tf->format(
                    [$level * 2, 15, '*'],
                    ['', $key, ''],
                    [Colors::C_LIGHTBLUE, Colors::C_LIGHTBLUE, Colors::C_LIGHTBLUE]
                );
                $this->printTable($value, $level + 1);
            } else {
                echo $tf->format(
                    [$level * 2, 15, '*'],
                    ['', $key, $value],
                    [Colors::C_LIGHTBLUE, Colors::C_LIGHTBLUE, Colors::C_LIGHTGRAY]
                );
            }
        }
    }

    /**
     * Check chunk availability for a given page
     *
     * @param string $page
     * @return void
     */
    protected function page($page)
    {
        $indexer = new Indexer();
        $pages = $indexer->getPages();
        $pos = array_search(cleanID($page), $pages);

        if ($pos === false) {
            $this->error('Page not found');
            return;
        }

        $storage = $this->helper->getStorage();
        $chunks = $storage->getPageChunks($page, $pos * 100);
        if ($chunks) {
            $this->success('Found ' . count($chunks) . ' chunks');
        } else {
            $this->error('No chunks found');
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
            $this->helper->getModel()->resetUsageStats();
            $result = $this->helper->askChatQuestion($q, $history);
            $this->colors->ptln("Interpretation: {$result['question']}", Colors::C_LIGHTPURPLE);
            $history[] = [$result['question'], $result['answer']];
            $this->printAnswer($result);
        }
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
        $this->printSources($sources);
    }

    /**
     * Run the maintenance tasks
     *
     * @return void
     */
    protected function runMaintenance()
    {
        $start = time();
        $this->helper->getStorage()->runMaintenance();
        $this->notice('Peak memory used: {memory}', ['memory' => filesize_h(memory_get_peak_usage(true))]);
        $this->notice('Spent time: {time}min', ['time' => round((time() - $start) / 60, 2)]);
    }

    /**
     * Recreate chunks and embeddings for all pages
     *
     * @return void
     * @todo make skip regex configurable
     */
    protected function createEmbeddings($clear)
    {
        $start = time();
        $this->helper->getEmbeddings()->createNewIndex('/(^|:)(playground|sandbox)(:|$)/', $clear);
        $this->notice('Peak memory used: {memory}', ['memory' => filesize_h(memory_get_peak_usage(true))]);
        $this->notice('Spent time: {time}min', ['time' => round((time() - $start) / 60, 2)]);
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
        $this->printSources($answer['sources']);
        echo "\n";
        $this->printUsage();
    }

    /**
     * Print the given sources
     *
     * @param Chunk[] $sources
     * @return void
     */
    protected function printSources($sources)
    {
        foreach ($sources as $source) {
            /** @var Chunk $source */
            $this->colors->ptln(
                "\t" . $source->getPage() . ' ' . $source->getId() . ' (' . $source->getScore() . ')',
                Colors::C_LIGHTBLUE
            );
        }
    }

    /**
     * Print the usage statistics for OpenAI
     *
     * @return void
     */
    protected function printUsage()
    {
        $this->info(
            'Made {requests} requests in {time}s to Model. Used {tokens} tokens for about ${cost}.',
            $this->helper->getModel()->getUsageStats()
        );
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

