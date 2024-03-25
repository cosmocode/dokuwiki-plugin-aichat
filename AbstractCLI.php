<?php

namespace dokuwiki\plugin\aichat;

use splitbrain\phpcli\Options;

abstract class AbstractCLI extends \dokuwiki\Extension\CLIPlugin
{
    /** @var \helper_plugin_aichat */
    protected $helper;

    /** @inheritdoc */
    public function __construct($autocatch = true)
    {
        parent::__construct($autocatch);
        $this->helper = plugin_load('helper', 'aichat');
        $this->helper->setLogger($this);
        $this->loadConfig();
        ini_set('memory_limit', -1);
    }

    /** @inheritdoc */
    protected function setup(Options $options)
    {
        $options->useCompactHelp();

        $options->registerOption(
            'lang',
            'When set to a language code, it overrides the the lang and preferUIlanguage settings and asks the ' .
            'bot to always use this language instead. ' .
            'When set to "auto" the bot is asked to detect the language of the input falling back to the wiki lang.',
            '',
            'lang'
        );
    }

    /** @inheritDoc */
    protected function main(Options $options)
    {
        if ($this->loglevel['debug']['enabled']) {
            $this->helper->factory->setDebug(true);
        }

        $lc = $options->getOpt('lang');
        if ($lc === 'auto') {
            $this->helper->updateConfig(['preferUIlanguage' => 0]);
        } else if ($lc) {
            $this->helper->updateConfig(['preferUIlanguage' => 1]);
            global $conf;
            $conf['lang'] = $lc;
        }

    }
}
