<?php

use dokuwiki\plugin\aichat\AbstractCLI;
use splitbrain\phpcli\Colors;
use splitbrain\phpcli\Options;

/**
 * DokuWiki Plugin aichat (CLI Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class cli_plugin_aichat_simulate extends AbstractCLI
{
    /** @inheritDoc */
    public function getInfo()
    {
        $info = parent::getInfo();
        $info['desc'] = 'Run a prepared chat session against multiple LLM models';
        return $info;
    }

    /** @inheritDoc */
    protected function setup(Options $options)
    {
        parent::setup($options);

        $options->setHelp('Run a prepared chat session against multiple models');
        $options->registerArgument('input', 'A file with the chat questions. Each question separated by two newlines');
        $options->registerArgument('output', 'Where to write the result CSV to');

        $options->registerOption(
            'filter',
            'Use only models matching this case-insensitive regex (no delimiters)',
            'f',
            'regex'
        );
    }

    /** @inheritDoc */
    protected function main(Options $options)
    {
        parent::main($options);

        [$input, $output] = $options->getArgs();
        $questions = $this->readInputFile($input);
        $outFH = @fopen($output, 'w');
        if (!$outFH) throw new \Exception("Could not open $output for writing");

        $models = $this->helper->factory->getModels(true, 'chat');

        $results = [];
        foreach ($models as $name => $info) {
            if ($options->getOpt('filter') && !preg_match('/' . $options->getOpt('filter') . '/i', $name)) {
                continue;
            }
            $this->success("Running on $name...");
            $results[$name] = $this->simulate($questions, $info);
        }

        foreach ($this->records2rows($results) as $row) {
            fputcsv($outFH, $row);
        }
        fclose($outFH);
        $this->success("Results written to $output");
    }

    protected function simulate($questions, $model)
    {
        // override models
        $this->helper->factory->chatModel = $model['instance'];
        $this->helper->factory->rephraseModel = clone $model['instance'];

        $records = [];

        $history = [];
        foreach ($questions as $q) {
            $this->helper->getChatModel()->resetUsageStats();
            $this->helper->getRephraseModel()->resetUsageStats();
            $this->helper->getEmbeddingModel()->resetUsageStats();

            $this->colors->ptln($q, Colors::C_LIGHTPURPLE);
            try {
                $result = $this->helper->askChatQuestion($q, $history);
                $history[] = [$result['question'], $result['answer']];
                $this->colors->ptln($result['question'], Colors::C_LIGHTBLUE);
            } catch (Exception $e) {
                $this->error($e->getMessage());
                $this->debug($e->getTraceAsString());
                $result = ['question' => $q, 'answer' => "ERROR\n" . $e->getMessage(), 'sources' => []];
            }

            $record = [
                'question' => $q,
                'rephrased' => $result['contextQuestion'],
                'answer' => $result['answer'],
                'source.list' => implode("\n", $result['sources']),
                'source.time' => $this->helper->getEmbeddings()->timeSpent,
                ...$this->flattenStats('stats.embedding', $this->helper->getEmbeddingModel()->getUsageStats()),
                ...$this->flattenStats('stats.rephrase', $this->helper->getRephraseModel()->getUsageStats()),
                ...$this->flattenStats('stats.chat', $this->helper->getChatModel()->getUsageStats()),
            ];
            $records[] = $record;
            $this->colors->ptln($result['answer'], Colors::C_LIGHTCYAN);
        }

        return $records;
    }

    /**
     * Reformat the result array into a CSV friendly array
     */
    protected function records2rows(array $result): array
    {
        $rowkeys = [
            'question' => ['question', 'stats.embedding.cost', 'stats.embedding.time'],
            'rephrased' => ['rephrased', 'stats.rephrase.cost', 'stats.rephrase.time'],
            'sources' => ['source.list', '', 'source.time'],
            'answer' => ['answer', 'stats.chat.cost', 'stats.chat.time'],
        ];

        $models = array_keys($result);
        $numberOfRecords = count($result[$models[0]]);
        $rows = [];

        // write headers
        $row = [];
        $row[] = 'type';
        foreach ($models as $model) {
            $row[] = $model;
            $row[] = 'Cost USD';
            $row[] = 'Time s';
        }
        $rows[] = $row;

        // write rows
        for ($i = 0; $i < $numberOfRecords; $i++) {
            foreach ($rowkeys as $type => $keys) {
                $row = [];
                $row[] = $type;
                foreach ($models as $model) {
                    foreach ($keys as $key) {
                        if ($key) {
                            $row[] = $result[$model][$i][$key];
                        } else {
                            $row[] = '';
                        }
                    }
                }
                $rows[] = $row;
            }
        }


        return $rows;
    }


    /**
     * Prefix each key in the given stats array to be merged with a larger array
     *
     * @return array
     */
    protected function flattenStats(string $prefix, array $stats)
    {
        $result = [];
        foreach ($stats as $key => $value) {
            $result["$prefix.$key"] = $value;
        }
        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function readInputFile(string $file): array
    {
        if (!file_exists($file)) throw new \Exception("File not found: $file");
        $lines = file_get_contents($file);
        $questions = explode("\n\n", $lines);
        $questions = array_map('trim', $questions);
        return $questions;
    }
}
