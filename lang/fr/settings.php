<?php
/**
 * French settings for aichat plugin
 */

$lang['chatmodel'] = 'Le modèle 🧠 à utiliser pour la complétion de chat. Configurez les identifiants nécessaires ci-dessous.';
$lang['rephrasemodel'] = 'Le modèle 🧠 à utiliser pour reformuler les questions. Configurez les identifiants nécessaires ci-dessous.';
$lang['embedmodel'] = 'Le modèle 🧠 à utiliser pour l\'embedding de texte. Configurez les identifiants nécessaires ci-dessous.<br>🔄 Vous devez reconstruire le stockage vectoriel après modification de ce paramètre.';
$lang['storage'] = 'Quel stockage vectoriel 💾 utiliser. Configurez les identifiants nécessaires ci-dessous.<br>🔄 Vous devez reconstruire le stockage vectoriel après modification de ce paramètre.';
$lang['customprompt'] = 'Une invite personnalisée ajoutée à celle utilisée par cette extension lors des requêtes au modèle IA. Pour plus de cohérence, elle doit être en anglais.';

$lang['openai_apikey'] = '🧠 Clé API <b>OpenAI</b>';
$lang['openai_org'] = '🧠 ID d\'organisation <b>OpenAI</b> (le cas échéant)';
$lang['gemini_apikey'] = '🧠 Clé API Google <b>Gemini</b>';
$lang['anthropic_apikey'] = '🧠 Clé API <b>Anthropic</b>';
$lang['mistral_apikey'] = '🧠 Clé API <b>Mistral</b>';
$lang['voyageai_apikey'] = '🧠 Clé API <b>Voyage AI</b>';
$lang['reka_apikey'] = '🧠 Clé API <b>Reka</b>';
$lang['groq_apikey'] = '🧠 Clé API <b>Groq</b>';
$lang['ollama_baseurl'] = '🧠 URL de base <b>Ollama</b>';

$lang['pinecone_apikey'] = '💾 Clé API <b>Pinecone</b>';
$lang['pinecone_baseurl'] = '💾 URL de base <b>Pinecone</b>';

$lang['chroma_baseurl'] = '💾 URL de base <b>Chroma</b>';
$lang['chroma_apikey'] = '💾 Clé API <b>Chroma</b>. Laisser vide si aucune authentification n\'est requise';
$lang['chroma_tenant'] = '💾 Nom du tenant <b>Chroma</b>';
$lang['chroma_database'] = '💾 Nom de la base de données <b>Chroma</b>';
$lang['chroma_collection'] = '💾 Collection <b>Chroma</b>. Sera créée.';

$lang['qdrant_baseurl'] = '💾 URL de base <b>Qdrant</b>';
$lang['qdrant_apikey'] = '💾 Clé API <b>Qdrant</b>. Laisser vide si aucune authentification n\'est requise';
$lang['qdrant_collection'] = '💾 Collection <b>Qdrant</b>. Sera créée.';

$lang['chunkSize'] = 'Nombre maximal de jetons par bloc.<br>🔄 Vous devez reconstruire le stockage vectoriel après modification de ce paramètre.';
$lang['similarityThreshold'] = 'Seuil minimal de similarité lors de la sélection des sources pour une question. 0-100.';
$lang['contextChunks'] = 'Nombre maximal de blocs à envoyer au modèle IA pour le contexte.';
$lang['chatHistory'] = 'Nombre de messages précédents à prendre en compte pour le contexte de la conversation.';
$lang['rephraseHistory'] = 'Nombre de messages précédents à prendre en compte lors de la reformulation d\'une question. Mettre 0 pour désactiver la reformulation.';

$lang['logging'] = 'Journaliser toutes les questions et réponses. Utilisez le <a href="?do=admin&page=logviewer&facility=aichat">visionneur de logs</a> pour y accéder.';
$lang['restrict'] = 'Restreindre l\'accès à ces utilisateurs et groupes (séparés par des virgules). Laisser vide pour autoriser tous les utilisateurs.';
$lang['skipRegex'] = 'Ignorer l\'indexation des pages correspondant à cette expression régulière (sans délimiteurs).<br>🔄 Vous devez reconstruire le stockage vectoriel après modification de ce paramètre.';
$lang['matchRegex'] = 'Indexer uniquement les pages correspondant à cette expression régulière (sans délimiteurs).<br>🔄 Vous devez reconstruire le stockage vectoriel après modification de ce paramètre.';
$lang['ignoreRegex'] = 'Ignorer les parties du contenu correspondant à cette expression régulière (sans délimiteurs).<br>🔄 Vous devez reconstruire le stockage vectoriel après modification de ce paramètre.';
$lang['preferUIlanguage'] = 'Comment gérer les wikis multilingues ? (nécessite le plugin de traduction)';

$lang['preferUIlanguage_o_0'] = 'Deviner la langue, utiliser toutes les sources';
$lang['preferUIlanguage_o_1'] = 'Préférer la langue de l\'interface, utiliser toutes les sources';
$lang['preferUIlanguage_o_2'] = 'Préférer la langue de l\'interface, sources uniquement dans la même langue';
