<?php

/**
 * Default settings for the aichat plugin
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 */

$conf['openaikey']    = '';
$conf['openaiorg']    = '';
$conf['model'] = 'OpenAI\\GPT35Turbo';

$conf['chatmodel'] = 'OpenAI gpt-3.5-turbo';
$conf['embedmodel'] = 'OpenAI text-embedding-ada-002';
$conf['storage'] = 'SQLite';

$conf['anthropic_key'] = '';

$conf['pinecone_apikey'] = '';
$conf['pinecone_baseurl'] = '';

$conf['chroma_baseurl'] = '';
$conf['chroma_apikey'] = '';
$conf['chroma_tenant'] = 'default_tenant';
$conf['chroma_database'] = 'default_database';
$conf['chroma_collection'] = 'aichat';

$conf['qdrant_baseurl'] = '';
$conf['qdrant_apikey'] = '';
$conf['qdrant_collection'] = 'aichat';

$conf['chunkSize'] = 1500;
$conf['contextChunks'] = 5;

$conf['logging'] = 0;
$conf['restrict'] = '';
$conf['skipRegex'] = ':(playground|sandbox)(:|$)';
$conf['matchRegex'] = '';
$conf['preferUIlanguage'] = 0;
