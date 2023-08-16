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
$lang['preferUIlanguage'] = 'Prefer the configured UI language when answering questions instead of guessing which language the user used in their question.';
