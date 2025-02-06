<?php

namespace dokuwiki\plugin\aichat;

use dokuwiki\plugin\config\core\Setting\SettingString;

/**
 * ModelSetting
 *
 * A setting for selecting a model. We're using a datalist to provide a list of known models but allow free input.
 */
class ModelSetting extends SettingString
{
    protected $modeltype;

    /** @inheritdoc */
    public function __construct($key, $params = null)
    {
        parent::__construct($key, $params);
        $this->modeltype = $params['type'] ?? 'chat';
    }

    /** @inheritdoc */
    public function html(\admin_plugin_config $plugin, $echo = false)
    {
        [$label, $input]  = parent::html($plugin, $echo);

        $choices = [];
        $jsons = glob(__DIR__ . '/Model/*/models.json');
        foreach ($jsons as $json) {
            $models = json_decode(file_get_contents($json), true);
            if (!isset($models[$this->modeltype])) continue;

            $namespace = basename(dirname($json));
            foreach (array_keys($models[$this->modeltype]) as $model) {
                $choices[] = "$namespace $model";
            }
        }
        sort($choices);

        $key = htmlspecialchars($this->key);
        $input = substr($input, 0, -2); // remove the closing tag
        $input = $input . ' list="config___' . $key . '_datalist" />';
        $datalist = '<datalist id="config___' . $key . '_datalist">';
        foreach ($choices as $choice) {
            $datalist .= '<option value="' . htmlspecialchars($choice) . '">';
        }
        $datalist .= '</datalist>';
        $input .= $datalist;

        return [$label, $input];
    }


}
