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

$conf['customprompt'] = '';

$conf['openai_apikey']    = '';
$conf['openai_org']    = '';

$conf['gemini_apikey'] = '';

$conf['anthropic_apikey'] = '';

$conf['mistral_apikey'] = '';

$conf['voyageai_apikey'] = '';

$conf['reka_apikey'] = '';

$conf['groq_apikey'] = '';

$conf['ollama_baseurl'] = '';

$conf['generic_apikey'] = '';
$conf['generic_apiurl'] = '';

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
$conf['fullpagecontext'] = 0;
$conf['chatHistory'] = 1;
$conf['rephraseHistory'] = 1;

$conf['logging'] = 0;
$conf['restrict'] = '';
$conf['skipRegex'] = ':(playground|sandbox)(:|$)';
$conf['matchRegex'] = '';
$conf['ignoreRegex'] = '';
$conf['preferUIlanguage'] = 0;
