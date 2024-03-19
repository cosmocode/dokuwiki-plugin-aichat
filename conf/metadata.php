<?php

/**
 * Options for the aichat plugin
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 */

$meta['chatmodel'] = array(\dokuwiki\plugin\aichat\ModelSetting::class, 'type' => 'chat');
$meta['embedmodel'] = array(\dokuwiki\plugin\aichat\ModelSetting::class, 'type' => 'embedding');
$meta['storage'] = array('multichoice',
    '_choices' => array(
        'Chroma',
        'Pinecone',
        'Qdrant',
        'SQLite',
    )
);

$meta['openai_apikey'] = array('password');
$meta['openai_org'] = array('string');

$meta['anthropic_apikey'] = array('password');

$meta['mistral_apikey'] = array('password');

$meta['pinecone_apikey'] = array('password');
$meta['pinecone_baseurl'] = array('string');

$meta['chroma_baseurl'] = array('string');
$meta['chroma_apikey'] = array('password');
$meta['chroma_tenant'] = array('string');
$meta['chroma_database'] = array('string');
$meta['chroma_collection'] = array('string');

$meta['qdrant_baseurl'] = array('string');
$meta['qdrant_apikey'] = array('password');
$meta['qdrant_collection'] = array('string');

$meta['chunkSize'] = array('numeric', '_min' => 100);
$meta['contextChunks'] = array('numeric', '_min' => 1);

$meta['logging'] = array('onoff');
$meta['restrict'] = array('string');
$meta['skipRegex'] = array('string');
$meta['matchRegex'] = array('string');
$meta['preferUIlanguage'] = array('multichoice', '_choices' => array(
    \dokuwiki\plugin\aichat\AIChat::LANG_AUTO_ALL,
    \dokuwiki\plugin\aichat\AIChat::LANG_UI_ALL,
    \dokuwiki\plugin\aichat\AIChat::LANG_UI_LIMITED,
));
