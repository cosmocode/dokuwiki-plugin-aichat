<?php

/**
 * Default settings for the aichat plugin
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 */

$conf['chatmodel'] = 'OpenAI gpt-3.5-turbo';
$conf['rephrasemodel'] = 'OpenAI gpt-3.5-turbo';
$conf['embedmodel'] = 'OpenAI text-embedding-ada-002';
$conf['storage'] = 'SQLite';

$conf['openai_apikey']    = '';
$conf['openai_org']    = '';

$conf['anthropic_apikey'] = '';

$conf['mistral_apikey'] = '';

$conf['voyageai_apikey'] = '';

$conf['ollama_baseurl'] = 'http://localhost:11434/api/';

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
$conf['similarityThreshold'] = 75;
$conf['contextChunks'] = 5;
$conf['chatHistory'] = 1;
$conf['rephraseHistory'] = 1;

$conf['logging'] = 0;
$conf['restrict'] = '';
$conf['skipRegex'] = ':(playground|sandbox)(:|$)';
$conf['matchRegex'] = '';
$conf['preferUIlanguage'] = 0;
