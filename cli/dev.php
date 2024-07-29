<?php

use dokuwiki\HTTP\DokuHTTPClient;
use dokuwiki\plugin\aichat\AbstractCLI;
use splitbrain\phpcli\Options;

/**
 * DokuWiki Plugin aichat (CLI Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */
class cli_plugin_aichat_dev extends AbstractCLI
{
    /** @inheritDoc */
    protected function setup(Options $options)
    {
        $options->setHelp('Helps with development of this plugin');

        $options->registerCommand('update', 'Update the model data');
    }

    /** @inheritDoc */
    protected function main(Options $options)
    {
        parent::main($options);

        switch ($options->getCmd()) {
            case 'update':
                $this->updateModelData();
                break;
            default:
                echo $options->help();
        }
    }

    protected function updateModelData()
    {

        $http = new DokuHTTPClient();
        $url = 'https://raw.githubusercontent.com/BerriAI/litellm/main/model_prices_and_context_window.json';
        $response = $http->get($url);
        if ($response === false) {
            $this->error('Failed to fetch model data');
            return 1;
        }
        $models = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        $ourProviders = [
            'anthropic' => [
                'name' => 'Anthropic',
            ],
            'groq' => [
                'name' => 'Groq',
                'skip' => '/-preview$/'
            ],
            'mistral' => [
                'name' => 'Mistral',
                'skip' => '/-\d\d\d\d$/',
            ],
            'openai' => [
                'name' => 'OpenAI',
                'skip' => '/(-\d\d\d\d-\d\d-\d\d|-preview|-\d\d\d\d)$|^ft:/'
            ],
            'reka' => [
                'name' => 'Reka',
            ],
            'voyage' => [
                'name' => 'VoyageAI',
                'skip' => '/-(01|02)(-|$)/', // outdated models
            ],
        ];

        // load existing models
        foreach ($ourProviders as $provider => $data) {
            $ourProviders[$provider]['models'] = json_decode(
                file_get_contents(__DIR__ . '/../Model/' . $data['name'] . '/' . 'models.json'),
                true
            );
        }

        // update models
        foreach ($models as $model => $data) {
            if (!isset($ourProviders[$data['litellm_provider']])) continue;
            if (!in_array($data['mode'], ['chat', 'embedding'])) continue;
            $provider = $data['litellm_provider'];
            $model = explode('/', (string) $model);
            $model = array_pop($model);

            if (isset($ourProviders[$provider]['skip']) && preg_match($ourProviders[$provider]['skip'], $model)) {
                $this->info('Skipping ' . $provider . ' ' . $model);
                continue;
            }
            $this->success("$provider $model");

            $oldmodel = $ourProviders[$provider]['models'][$data['mode']][$model] ?? [];
            $newmodel = [
                "description" => $oldmodel['description'] ?? $data['source'] ?? '',
                "inputTokens" => $data['max_input_tokens'] ?? $data['max_tokens'],
                "inputTokenPrice" => round($data['input_cost_per_token'] * 1_000_000, 2),
            ];

            if ($data['mode'] === 'chat') {
                $newmodel['outputTokens'] = $data['max_output_tokens'];
                $newmodel['outputTokenPrice'] = round($data['output_cost_per_token'] * 1_000_000, 2);
            } elseif (isset($oldmodel['dimensions'])) {
                $newmodel['dimensions'] = $oldmodel['dimensions'];
            } else {
                $this->warning('No dimensions for ' . $provider . ' ' . $model . '. Check manually!');
                $newmodel['dimensions'] = 1536;
            }
            $ourProviders[$provider]['models'][$data['mode']][$model] = $newmodel;
        }

        // save models
        foreach ($ourProviders as $data) {
            file_put_contents(
                __DIR__ . '/../Model/' . $data['name'] . '/' . 'models.json',
                json_encode($data['models'], JSON_PRETTY_PRINT)
            );
        }

        return 0;
    }
}
