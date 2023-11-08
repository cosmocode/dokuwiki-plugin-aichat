<?php
/**
 * english language file for aichat plugin
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 */

$lang['openaikey'] = 'Your OpenAI API key';
$lang['openaiorg'] = 'Your OpenAI organization ID (if any)';
$lang['model'] = 'Which model to use. When changing models, be sure to run <code>php bin/plugin.php aichat embed -c</code> to rebuild the vector storage.';

$lang['pinecone_apikey'] = 'Your Pinecone API key if you want to use Pinecone as a storage backend.';
$lang['pinecone_baseurl'] = 'Your Pinecone base URL if you want to use Pinecone as a storage backend.';

$lang['chroma_baseurl'] = 'Your Chroma base URL if you want to use Chroma as a storage backend.';
$lang['chroma_apikey'] = 'Your Chroma API key. Empty if no authentication is required.';
$lang['chroma_tenant'] = 'Your Chroma tenant name.';
$lang['chroma_database'] = 'Your Chroma database name.';
$lang['chroma_collection'] = 'The collection to use. Will be created.';

$lang['qdrant_baseurl'] = 'Your Qdrant base URL if you want to use Qdrant as a storage backend.';
$lang['qdrant_apikey'] = 'Your Qdrant API key. Empty if no authentication is required.';
$lang['qdrant_collection'] = 'The collection to use. Will be created.';

$lang['logging'] = 'Log all questions and answers. Use the <a href="?do=admin&page=logviewer&facility=aichat">Log Viewer</a> to access.';
$lang['restrict'] = 'Restrict access to these users and groups (comma separated). Leave empty to allow all users.';
$lang['preferUIlanguage'] = 'How to work with multilingual wikis? (Requires the translation plugin)';

$lang['preferUIlanguage_o_0'] = 'Guess language, use all sources';
$lang['preferUIlanguage_o_1'] = 'Prefer UI language, use all sources';
$lang['preferUIlanguage_o_2'] = 'Prefer UI language, same language sources only';
