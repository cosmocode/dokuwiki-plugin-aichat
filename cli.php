<?php

use dokuwiki\plugin\aichat\Embeddings;
use dokuwiki\plugin\aichat\OpenAI;
use Hexogen\KDTree\FSKDTree;
use Hexogen\KDTree\FSTreePersister;
use Hexogen\KDTree\Item;
use Hexogen\KDTree\ItemFactory;
use Hexogen\KDTree\ItemList;
use Hexogen\KDTree\KDTree;
use Hexogen\KDTree\NearestSearch;
use Hexogen\KDTree\Point;
use splitbrain\phpcli\Colors;
use splitbrain\phpcli\Options;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * DokuWiki Plugin aichat (CLI Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class cli_plugin_aichat extends \dokuwiki\Extension\CLIPlugin
{

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
        /** @var helper_plugin_aichat_prompt $prompt */
        $prompt = plugin_load('helper', 'aichat_prompt');

        $history = [];
        while ($q = $this->readLine('Your Question')) {
            if ($history) {
                $question = $prompt->rephraseChatQuestion($q, $history);
                $this->colors->ptln("Interpretation: $question", Colors::C_LIGHTPURPLE);
            } else {
                $question = $q;
            }
            $result = $prompt->askQuestion($question);
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
            $this->colors->ptln("\t".$source['meta']['pageid'], Colors::C_LIGHTBLUE);
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
        /** @var helper_plugin_aichat_prompt $prompt */
        $prompt = plugin_load('helper', 'aichat_prompt');

        $result = $prompt->askQuestion($query);
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
        $openAI = new OpenAI($this->getConf('openaikey'), $this->getConf('openaiorg'));
        $embedding = new Embeddings($openAI, $this);

        $sources = $embedding->getSimilarChunks($query);
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
        $openAI = new OpenAI($this->getConf('openaikey'), $this->getConf('openaiorg'));

        $embeddings = new Embeddings($openAI, $this);
        $embeddings->createNewIndex();
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

