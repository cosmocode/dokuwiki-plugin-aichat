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
            default:
                echo $options->help();
        }
    }

    protected function ask($query) {
        /** @var helper_plugin_aichat_prompt $prompt */
        $prompt = plugin_load('helper', 'aichat_prompt');
        
        $result = $prompt->askQuestion($query);

        echo $result['answer'];
        echo "\n\nSources:\n";
        foreach($result['sources'] as $source) {
            echo $source['meta']['pageid'] . "\n";
        }
    }

    protected function similar($query)
    {

        $openAI = new OpenAI($this->getConf('openaikey'), $this->getConf('openaiorg'));

        $embedding = new Embeddings($openAI, $this);

        var_dump($embedding->getSimilarChunks($query));
    }

    protected function createEmbeddings()
    {
        $openAI = new OpenAI($this->getConf('openaikey'), $this->getConf('openaiorg'));

        $embeddings = new Embeddings($openAI, $this);
        $embeddings->createNewIndex();
    }


}

