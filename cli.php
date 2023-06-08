<?php

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
    }


    /** @inheritDoc */
    protected function setup(Options $options)
    {
        $options->setHelp('Manage the AI chatbot data');

        $options->registerCommand('embed', 'Create embeddings for all pages');

        $options->registerCommand('similar', 'Search for similar pages');
        $options->registerArgument('query', 'Look up chunks similar to this query', true, 'similar');

        $options->registerCommand('ask', 'Ask a question');
        $options->registerArgument('question', 'The question to ask', true, 'ask');

        $options->registerCommand('chat', 'Start an interactive chat session');
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
            default:
                echo $options->help();
        }
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
            $this->colors->ptln("\t" . $source['meta']['pageid'], Colors::C_LIGHTBLUE);
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
            $this->colors->ptln($source['meta']['pageid'], Colors::C_LIGHTBLUE);
        }
    }

    /**
     * Recreate chunks and embeddings for all pages
     *
     * @return void
     */
    protected function createEmbeddings()
    {
        $this->helper->getEmbeddings()->createNewIndex();
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

