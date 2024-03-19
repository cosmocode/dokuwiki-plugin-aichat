<?php
/**
 * english language file for aichat plugin
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 */


$lang['chatmodel'] = 'The 🧠 model to use for chat completion. Configure required credentials below.';
$lang['embedmodel'] = 'The 🧠 model to use for text embedding. Configure required credentials below.<br>🔄 You need to rebuild the vector storage when changing this setting.';
$lang['storage'] = 'Which 📥 vector storage to use. Configure required credentials below.<br>🔄 You need to rebuild the vector storage when changing this setting.';


$lang['openai_apikey'] = '🧠 <b>OpenAI</b> API key';
$lang['openai_org'] = '🧠 <b>OpenAI</b> Organization ID (if any)';
$lang['anthropic_apikey'] = '🧠 <b>Anthropic</b> API key';
$lang['mistral_apikey'] = '🧠 <b>Mistral</b> API key';
$lang['voyageai_apikey'] = '🧠 <b>Voyage AI</b> API key';

$lang['pinecone_apikey'] = '📥 <b>Pinecone</b> API key';
$lang['pinecone_baseurl'] = '📥 <b>Pinecone</b> base URL';

$lang['chroma_baseurl'] = '📥 <b>Chroma</b> base URL';
$lang['chroma_apikey'] = '📥 <b>Chroma</b> API key. Empty if no authentication is required';
$lang['chroma_tenant'] = '📥 <b>Chroma</b> tenant name';
$lang['chroma_database'] = '📥 <b>Chroma</b> database name';
$lang['chroma_collection'] = '📥 <b>Chroma</b> collection. Will be created.';

$lang['qdrant_baseurl'] = '📥 <b>Qdrant</b> base URL';
$lang['qdrant_apikey'] = '📥 <b>Qdrant</b> API key. Empty if no authentication is required';
$lang['qdrant_collection'] = '📥 <b>Qdrant</b> collection. Will be created.';

$lang['chunkSize'] = 'Maximum number of tokens per chunk.<br>🔄 You need to rebuild the vector storage when changing this setting.';
$lang['contextChunks'] = 'Number of chunks to send to the AI model for context.';

$lang['logging'] = 'Log all questions and answers. Use the <a href="?do=admin&page=logviewer&facility=aichat">Log Viewer</a> to access.';
$lang['restrict'] = 'Restrict access to these users and groups (comma separated). Leave empty to allow all users.';
$lang['skipRegex'] = 'Skip indexing pages matching this regular expression (no delimiters).';
$lang['matchRegex'] = 'Only index pages matching this regular expression (no delimiters).';
$lang['preferUIlanguage'] = 'How to work with multilingual wikis? (Requires the translation plugin)';

$lang['preferUIlanguage_o_0'] = 'Guess language, use all sources';
$lang['preferUIlanguage_o_1'] = 'Prefer UI language, use all sources';
$lang['preferUIlanguage_o_2'] = 'Prefer UI language, same language sources only';
