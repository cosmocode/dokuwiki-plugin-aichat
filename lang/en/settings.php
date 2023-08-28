<?php
/**
 * english language file for aichat plugin
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 */

$lang['openaikey'] = 'Your OpenAI API key';
$lang['openaiorg'] = 'Your OpenAI organization ID (if any)';
$lang['model'] = 'Which model to use. When changing models, be sure to run <code>php bin/plugin.php aichat embed -c</code> to rebuild the vector storage.';
$lang['logging'] = 'Log all questions and answers. Use the <a href="?do=admin&page=logviewer&facility=aichat">Log Viewer</a> to access.';
$lang['restrict'] = 'Restrict access to these users and groups (comma separated). Leave empty to allow all users.';
$lang['preferUIlanguage'] = 'How to work with multilingual wikis? (Requires the translation plugin)';

$lang['preferUIlanguage_o_0'] = 'Guess language, use all sources';
$lang['preferUIlanguage_o_1'] = 'Prefer UI language, use all sources';
$lang['preferUIlanguage_o_2'] = 'Prefer UI language, same language sources only';
