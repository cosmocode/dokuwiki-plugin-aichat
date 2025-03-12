<?php

use dokuwiki\Extension\RemotePlugin;
use dokuwiki\plugin\aichat\RemoteResponse\Chunk;
use dokuwiki\plugin\aichat\RemoteResponse\LlmReply;
use dokuwiki\Remote\AccessDeniedException;

/**
 * DokuWiki Plugin aichat (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Andreas Gohr <gohr@cosmocode.de>
 */
class remote_plugin_aichat extends RemotePlugin
{

    /**
     * Initialize the helper and check permissions
     *
     * @param string $model
     * @param string $lang
     * @return helper_plugin_aichat
     * @throws AccessDeniedException
     */
    protected function initHelper($model = '', $lang = '')
    {
        /** @var helper_plugin_aichat $helper */
        $helper = plugin_load('helper', 'aichat');
        if ($model) {
            $helper->updateConfig(
                ['chatmodel' => $model, 'rephasemodel' => $model]
            );
        }

        if (!$helper->userMayAccess()) {
            throw new AccessDeniedException('You are not allowed to use this plugin', 111);
        }

        if ($lang === 'auto') {
            $helper->updateConfig(['preferUIlanguage' => 0]);
        } elseif ($lang) {
            $helper->updateConfig(['preferUIlanguage' => 1]);
            global $conf;
            $conf['lang'] = $lang;
        }

        return $helper;
    }

    /**
     * Ask the LLM a question
     *
     * Sends the given question to the LLM and returns the answer, including the used sources.
     *
     * @param string $query The question to ask the LLM
     * @param string $model The model to use, if empty the default model is used
     * @param string $lang Language code to override preferUIlanguage setting. "auto" to force autodetection.
     * @return LlmReply
     * @throws AccessDeniedException
     * @throws Exception
     */
    public function ask($query, $model = '', $lang = '')
    {
        $helper = $this->initHelper($model, $lang);
        $result = $helper->askQuestion($query);

        return new LlmReply($result);
    }

    /**
     * Get page chunks similar to a given query
     *
     * Uses the given query to find similar pages in the wiki. Returns the most similar chunks.
     *
     * This call returns chunks, not pages. So a page may returned multiple times when different chunks of it
     * are similar to the query.
     *
     * Note that this call may return less results than requested depending on the used vector store.
     *
     * @param string $query
     * @param int $max Maximum number of results to return. -1 for default set in config
     * @param float $threshold Minimum similarity score to return results for. -1 for default set in config
     * @param string $lang Language code to override preferUIlanguage setting. "auto" to force autodetection.
     * @return Chunk[] A list of similar chunks
     * @throws AccessDeniedException
     * @throws Exception
     */
    public function similar($query, $max = -1, $threshold = -1, $lang = '')
    {
        $helper = $this->initHelper('', $lang);
        $langlimit = $helper->getLanguageLimit();

        $embeddings = $helper->getEmbeddings();
        if ($max !== -1) {
            $embeddings->setConfigContextChunks($max);
        }
        if ($threshold !== -1) {
            $embeddings->setSimilarityThreshold($threshold);
        }

        $sources = $embeddings->getSimilarChunks($query, $langlimit, false);

        $results = [];
        foreach ($sources as $source) {
            $results[] = new Chunk($source);
        }
        return $results;
    }

}
