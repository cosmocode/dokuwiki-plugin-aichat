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

$meta['pinecone_apikey'] = array('string');
$meta['pinecone_baseurl'] = array('string');

$meta['logging'] = array('onoff');
$meta['restrict'] = array('string');
