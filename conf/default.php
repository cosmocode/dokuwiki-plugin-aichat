<?php

/**
 * Default settings for the aichat plugin
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 */

$conf['openaikey']    = '';
$conf['openaiorg']    = '';
$conf['model'] = 'OpenAI\\GPT35Turbo';

$conf['pinecone_apikey'] = '';
$conf['pinecone_baseurl'] = '';

$conf['chroma_baseurl'] = '';
$conf['chroma_apikey'] = '';
$conf['chroma_tenant'] = 'default_tenant';
$conf['chroma_database'] = 'default_database';
$conf['chroma_collection'] = 'aichat';

$conf['logging'] = 0;
$conf['restrict'] = '';
$conf['preferUIlanguage'] = 0;
