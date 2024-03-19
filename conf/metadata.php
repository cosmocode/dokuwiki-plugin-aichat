<?php

/**
 * Options for the aichat plugin
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 */

$meta['openaikey'] = array('string');
$meta['openaiorg'] = array('string');

$meta['model'] = array('multichoice',
    '_choices' => array(
        'OpenAI\\GPT35Turbo',
        'OpenAI\\GPT35Turbo16k',
        'OpenAI\\GPT4',
    )
);


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




$meta['pinecone_apikey'] = array('string');
$meta['pinecone_baseurl'] = array('string');

$meta['chroma_baseurl'] = array('string');
$meta['chroma_apikey'] = array('string');
$meta['chroma_tenant'] = array('string');
$meta['chroma_database'] = array('string');
$meta['chroma_collection'] = array('string');

$meta['qdrant_baseurl'] = array('string');
$meta['qdrant_apikey'] = array('string');
$meta['qdrant_collection'] = array('string');

$meta['logging'] = array('onoff');
$meta['restrict'] = array('string');
$meta['skipRegex'] = array('string');
$meta['matchRegex'] = array('string');
$meta['preferUIlanguage'] = array('multichoice', '_choices' => array(
    \dokuwiki\plugin\aichat\AIChat::LANG_AUTO_ALL,
    \dokuwiki\plugin\aichat\AIChat::LANG_UI_ALL,
    \dokuwiki\plugin\aichat\AIChat::LANG_UI_LIMITED,
));
