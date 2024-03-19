<?php

namespace dokuwiki\plugin\aichat;


use dokuwiki\plugin\config\core\Setting\SettingMultichoice;

class ModelSetting extends SettingMultichoice {

    /** @inheritdoc */
    public function __construct($key, $params = null)
    {
        parent::__construct($key, $params);

        $type = $params['type'] ?? 'chat';

        $jsons = glob(__DIR__ . '/Model/*/models.json');
        foreach ($jsons as $json) {
            $models = json_decode(file_get_contents($json), true);
            if(!isset($models[$type])) continue;

            $namespace = basename(dirname($json));
            foreach (array_keys($models[$type]) as $model) {
                $this->choices[] = "$namespace $model";
            }
        }
        sort($this->choices);
    }
}
