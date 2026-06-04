<?php

/**
 * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)
 *
 * @author Wizzard <wizzardsk@gmail.com>
 */
$lang['chatmodel']             = '🧠 model pre chat completion. Potrebné prihlasovacie údaje nastavte nižšie.';
$lang['rephrasemodel']         = '🧠 model na preformulovanie otázok. Potrebné prihlasovacie údaje nastavte nižšie.';
$lang['embedmodel']            = '🧠 model pre text embedding. Potrebné prihlasovacie údaje nastavte nižšie.<br>🔄 Pri zmene tohto nastavenia musíte znovu zostaviť vector storage.';
$lang['storage']               = 'Ktoré 📥 vector storage použiť. Potrebné prihlasovacie údaje nastavte nižšie.<br>🔄 Pri zmene tohto nastavenia musíte znovu zostaviť vector storage.';
$lang['customprompt']          = 'Vlastný prompt pridaný k promptu používanému týmto pluginom pri dopytovaní AI modelu. Pre konzistentnosť by mal byť v angličtine.';
$lang['openai_apikey']         = '🧠 <b>OpenAI</b> API kľúč';
$lang['openai_org']            = '🧠 <b>OpenAI</b> Organization ID (ak existuje)';
$lang['gemini_apikey']         = '🧠 Google <b>Gemini</b> API kľúč';
$lang['anthropic_apikey']      = '🧠 <b>Anthropic</b> API kľúč';
$lang['mistral_apikey']        = '🧠 <b>Mistral</b> API kľúč';
$lang['voyageai_apikey']       = '🧠 <b>Voyage AI</b> API kľúč';
$lang['reka_apikey']           = '🧠 <b>Reka</b> API kľúč';
$lang['groq_apikey']           = '🧠 <b>Groq</b> API kľúč';
$lang['ollama_apiurl']         = '🧠 <b>Ollama</b> base URL';
$lang['ollama_apikey']         = '🧠 <b>Ollama</b> API kľúč (voliteľné)';
$lang['generic_apikey']        = '🧠 <b>Generic</b> (OpenAI kompatibilný) API kľúč';
$lang['generic_apiurl']        = '🧠 <b>Generic</b> (OpenAI kompatibilná) API URL';
$lang['pinecone_apikey']       = '📥 <b>Pinecone</b> API kľúč';
$lang['pinecone_baseurl']      = '📥 <b>Pinecone</b> base URL';
$lang['chroma_baseurl']        = '📥 <b>Chroma</b> base URL';
$lang['chroma_apikey']         = '📥 <b>Chroma</b> API kľúč. Prázdne, ak sa nevyžaduje autentifikácia';
$lang['chroma_tenant']         = '📥 <b>Chroma</b> názov tenanta';
$lang['chroma_database']       = '📥 <b>Chroma</b> názov databázy';
$lang['chroma_collection']     = '📥 <b>Chroma</b> kolekcia. Bude vytvorená.';
$lang['qdrant_baseurl']        = '📥 <b>Qdrant</b> base URL';
$lang['qdrant_apikey']         = '📥 <b>Qdrant</b> API kľúč. Prázdne, ak sa nevyžaduje autentifikácia';
$lang['qdrant_collection']     = '📥 <b>Qdrant</b> kolekcia. Bude vytvorená.';
$lang['chunkSize']             = 'Maximálny počet tokenov na chunk.<br>🔄 Pri zmene tohto nastavenia musíte znovu zostaviť vector storage.';
$lang['similarityThreshold']   = 'Minimálny prah podobnosti pri výbere zdrojov pre otázku. 0-100.';
$lang['contextChunks']         = 'Maximálny počet chunkov odoslaných AI modelu ako kontext.';
$lang['fullpagecontext']       = 'Vždy posielať celý obsah stránky pre každý zodpovedajúci chunk ako kontext pre AI model. Neaplikujú sa žiadne tokenové limity, čo môže viesť k veľkým a drahým požiadavkám. Používajte iba s modelmi s veľkým kontextom! Tu nastavené číslo je maximálny počet odoslaných stránok. Nemôže byť väčšie než contextChunks. Hodnota 0 = vypnuté, použijú sa iba chunky.';
$lang['chatHistory']           = 'Počet predchádzajúcich správ chatu zohľadnených ako kontext konverzácie.';
$lang['rephraseHistory']       = 'Počet predchádzajúcich správ chatu zohľadnených ako kontext pri preformulovaní otázky. 0 = preformulovanie vypnuté.';
$lang['logging']               = 'Logovať všetky otázky a odpovede. Prístup cez <a href="?do=admin&page=logviewer&facility=aichat">Log Viewer</a>.';
$lang['restrict']              = 'Obmedziť prístup na týchto používateľov a skupiny (oddelené čiarkami). Prázdne = povoliť všetkých používateľov.';
$lang['skipRegex']             = 'Preskočiť indexovanie stránok zodpovedajúcich tomuto regulárnemu výrazu (bez oddeľovačov).<br>🔄 Pri zmene tohto nastavenia musíte znovu zostaviť vector storage.';
$lang['matchRegex']            = 'Indexovať iba stránky zodpovedajúce tomuto regulárnemu výrazu (bez oddeľovačov).<br>🔄 Pri zmene tohto nastavenia musíte znovu zostaviť vector storage.';
$lang['ignoreRegex']           = 'Ignorovať časti obsahu stránky zodpovedajúce tomuto regulárnemu výrazu (bez oddeľovačov).<br>🔄 Pri zmene tohto nastavenia musíte znovu zostaviť vector storage.';
$lang['preferUIlanguage']      = 'Ako pracovať s viacjazyčnými wiki? (Vyžaduje translation plugin)';
$lang['preferUIlanguage_o_0']  = 'Odhadnúť jazyk, použiť všetky zdroje';
$lang['preferUIlanguage_o_1']  = 'Uprednostniť jazyk rozhrania, použiť všetky zdroje';
$lang['preferUIlanguage_o_2']  = 'Uprednostniť jazyk rozhrania, iba zdroje v rovnakom jazyku';
