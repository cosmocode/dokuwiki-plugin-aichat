<?php

/**
 * @license    GPL 2 (https://www.gnu.org/licenses/gpl.html)
 *
 * @author Eduardo Mozart de Oliveira <eduardomozart182@gmail.com>
 */
$lang['chatmodel']             = 'O modelo 🧠 a ser usado para <i>chat completion</i>. Configure as credenciais necessárias abaixo.';
$lang['rephrasemodel']         = 'O modelo 🧠 a ser usado para <i>rephrasing questions</i>. Configure as credenciais necessárias abaixo.';
$lang['embedmodel']            = 'O modelo 🧠 a ser usado para <i>text embedding</i>. Configure as credenciais necessárias abaixo.<br>🔄 Você precisa reconstruir o armazenamento vetorial ao alterar essa configuração.';
$lang['storage']               = 'Qual 📥 armazenamento vetorial usar. Configure as credenciais necessárias abaixo.<br>🔄 Você precisa reconstruir o armazenamento vetorial ao alterar esta configuração.';
$lang['customprompt']          = 'Um prompt personalizado que é adicionado ao prompt usado por este plug-in ao consultar o modelo de IA. Para consistência, deve estar em inglês.';
$lang['openai_apikey']         = '🧠 Chave de API <b>OpenAI</b>';
$lang['openai_org']            = '🧠 ID da organização <b>OpenAI</b> (se houver)';
$lang['gemini_apikey']         = '🧠 Chave de API Google <b>Gemini</b>';
$lang['anthropic_apikey']      = '🧠 Chave de API <b>Anthropic</b>';
$lang['mistral_apikey']        = '🧠 Chave de API <b>Mistral</b>';
$lang['voyageai_apikey']       = '🧠 Chave de API <b>Voyage AI</b>';
$lang['reka_apikey']           = '🧠 Chave de API <b>Reka</b>';
$lang['groq_apikey']           = '🧠 Chave de API <b>Groq</b>';
$lang['ollama_apiurl']        = '🧠 URL base <b>Ollama</b>';
$lang['pinecone_apikey']       = '📥 Chave de API <b>Pinecone</b>';
$lang['pinecone_baseurl']      = '📥 URL base <b>Pinecone</b>';
$lang['chroma_baseurl']        = '📥 URL base <b>Chroma</b>';
$lang['chroma_apikey']         = '📥 Chave de API <b>Chroma</b>. Vazio se nenhuma autenticação for necessária.';
$lang['chroma_tenant']         = '📥 Nome do <i>tenant</i> <b>Chroma</b>';
$lang['chroma_database']       = '📥 Nome do banco de dados <b>Chroma</b>';
$lang['chroma_collection']     = '📥 Coleção <b>Chroma</b>. Será criada.';
$lang['qdrant_baseurl']        = '📥 URL base <b>Qdrant</b>';
$lang['qdrant_apikey']         = '📥 Chave de API <b>Qdrant</b>. Vazio se nenhuma autenticação for necessária.';
$lang['qdrant_collection']     = '📥 Coleção <b>Qdrant</b>. Será criada.';
$lang['chunkSize']             = 'Número máximo de tokens por bloco.<br>🔄 Você precisa reconstruir o armazenamento vetorial ao alterar esta configuração.';
$lang['similarityThreshold']   = 'Limite mínimo de similaridade ao selecionar fontes para uma pergunta. 0-100.';
$lang['contextChunks']         = 'Número máximo de blocos (<i>chunks</i>) a serem enviados ao modelo de IA para contexto.';
$lang['chatHistory']           = 'Número de mensagens de bate-papo anteriores a serem consideradas no contexto da conversa.';
$lang['rephraseHistory']       = 'Número de mensagens de bate-papo anteriores a serem consideradas para fins de contexto ao reformular uma pergunta. Defina como 0 para desativar a reformulação.';
$lang['logging']               = 'Registre todas as perguntas e respostas. Use o <a href="?do=admin&page=logviewer&facility=aichat">Ver logs</a> para acessar.';
$lang['restrict']              = 'Restrinja o acesso a esses usuários e grupos (separados por vírgula). Deixe em branco para permitir todos os usuários.';
$lang['skipRegex']             = 'Ignore as páginas de indexação que correspondam a esta expressão regular (sem delimitadores).<br>🔄 Você precisa reconstruir o armazenamento vetorial ao alterar esta configuração.';
$lang['matchRegex']            = 'Apenas indexe páginas que correspondam a esta expressão regular (sem delimitadores).<br>🔄 Você precisa reconstruir o armazenamento vetorial ao alterar esta configuração.';
$lang['ignoreRegex']           = 'Ignore partes do conteúdo da página que correspondam a esta expressão regular (sem delimitadores).<br>🔄 Você precisa reconstruir o armazenamento vetorial ao alterar esta configuração.';
$lang['preferUIlanguage']      = 'Como trabalhar com wikis multilíngues? (Requer o plugin <i>translation</i>)';
$lang['preferUIlanguage_o_0']  = 'Adivinhe o idioma, use todas as fontes';
$lang['preferUIlanguage_o_1']  = 'Prefira o idioma da UI, use todas as fontes';
$lang['preferUIlanguage_o_2']  = 'Prefira o idioma da UI, apenas fontes no mesmo idioma';
