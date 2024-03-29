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
            'c',
            false,
            'embed'
        );

        $options->registerCommand('maintenance', 'Run storage maintenance. Refert to the documentation for details.');

        $options->registerCommand('similar', 'Search for similar pages');
        $options->registerArgument('query', 'Look up chunks similar to this query', true, 'similar');

        $options->registerCommand('ask', 'Ask a question');
        $options->registerArgument('question', 'The question to ask', true, 'ask');

        $options->registerCommand('chat', 'Start an interactive chat session');

        $options->registerCommand('info', 'Get Info about the vector storage and other stats');

        $options->registerCommand('split', 'Split a page into chunks (for debugging)');
        $options->registerArgument('page', 'The page to split', true, 'split');

        $options->registerCommand('page', 'Check if chunks for a given page are available (for debugging)');
        $options->registerArgument('page', 'The page to check', true, 'page');
        $options->registerOption('dump', 'Dump the chunks', 'd', false, 'page');

        $options->registerCommand('tsv', 'Create TSV files for visualizing at http://projector.tensorflow.org/' .
            ' Not supported on all storages.');
        $options->registerArgument('vector.tsv', 'The vector file', false, 'tsv');
        $options->registerArgument('meta.tsv', 'The meta file', false, 'tsv');
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
                $this->page($options->getArgs()[0], $options->getOpt('dump'));
                break;
            case 'info':
                $this->showinfo();
                break;
            case 'tsv':
                $args = $options->getArgs();
                $vector = $args[0] ?? 'vector.tsv';
                $meta = $args[1] ?? 'meta.tsv';
                $this->tsv($vector, $meta);
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
        $stats = array_merge(
            $stats,
            array_map('dformat', $this->helper->getRunData()),
            $this->helper->getStorage()->statistics()
        );
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
                    [$level * 2, 20, '*'],
                    ['', $key, ''],
                    [Colors::C_LIGHTBLUE, Colors::C_LIGHTBLUE, Colors::C_LIGHTBLUE]
                );
                $this->printTable($value, $level + 1);
            } else {
                echo $tf->format(
                    [$level * 2, 20, '*'],
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
    protected function page($page, $dump = false)
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
            if ($dump) {
                echo json_encode($chunks, JSON_PRETTY_PRINT);
            }
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
        $langlimit = $this->helper->getLanguageLimit();
        if ($langlimit) {
            $this->info('Limiting results to {lang}', ['lang' => $langlimit]);
        }

        $sources = $this->helper->getEmbeddings()->getSimilarChunks($query, $langlimit);
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

        $data = $this->helper->getRunData();
        $data['maintenance ran at'] = time();
        $this->helper->setRunData($data);
    }

    /**
     * Recreate chunks and embeddings for all pages
     *
     * @return void
     */
    protected function createEmbeddings($clear)
    {
        [$skipRE, $matchRE] = $this->getRegexps();

        $start = time();
        $this->helper->getEmbeddings()->createNewIndex($skipRE, $matchRE, $clear);
        $this->notice('Peak memory used: {memory}', ['memory' => filesize_h(memory_get_peak_usage(true))]);
        $this->notice('Spent time: {time}min', ['time' => round((time() - $start) / 60, 2)]);

        $data = $this->helper->getRunData();
        $data['embed ran at'] = time();
        $this->helper->setRunData($data);
    }

    /**
     * Dump TSV files for debugging
     *
     * @return void
     */
    protected function tsv($vector, $meta)
    {

        $storage = $this->helper->getStorage();
        $storage->dumpTSV($vector, $meta);
        $this->success('written to ' . $vector . ' and ' . $meta);
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

    /**
     * Read the skip and match regex from the config
     *
     * Ensures the regular expressions are valid
     *
     * @return string[] [$skipRE, $matchRE]
     */
    protected function getRegexps()
    {
        $skip = $this->getConf('skipRegex');
        $skipRE = '';
        $match = $this->getConf('matchRegex');
        $matchRE = '';

        if ($skip) {
            $skipRE = '/' . $skip . '/';
            if (@preg_match($skipRE, '') === false) {
                $this->error(preg_last_error_msg());
                $this->error('Invalid regular expression in $conf[\'skipRegex\']. Ignored.');
                $skipRE = '';
            } else {
                $this->success('Skipping pages matching ' . $skipRE);
            }
        }

        if ($match) {
            $matchRE = '/' . $match . '/';
            if (@preg_match($matchRE, '') === false) {
                $this->error(preg_last_error_msg());
                $this->error('Invalid regular expression in $conf[\'matchRegex\']. Ignored.');
                $matchRE = '';
            } else {
                $this->success('Only indexing pages matching ' . $matchRE);
            }
        }
        return [$skipRE, $matchRE];
    }
}
