# AGENTS.md

This file provides guidance to LLM Code Agents when working with code in this repository.

## Overview

This is a DokuWiki plugin that enables AI-powered chat functionality using LLMs (Large Language Models) and RAG (Retrieval-Augmented Generation). The plugin indexes wiki pages as embeddings in a vector database and allows users to ask questions about wiki content.

## Development Commands

### Testing
```bash
../../../bin/plugin.php dev test
```

### CLI Commands
The plugin provides a CLI interface via `cli.php`:

```bash
# Get a list of available commands
../../../bin/plugin.php aichat --help
```

## Architecture

### Core Components

**helper.php (helper_plugin_aichat)**
- Main entry point for plugin functionality
- Manages model factory and configuration
- Handles question answering with context retrieval
- Prepares messages with chat history and token limits
- Implements question rephrasing for better context search

**Embeddings.php**
- Manages the vector embeddings index
- Splits pages into chunks using TextSplitter
- Creates and retrieves embeddings via embedding models
- Performs similarity searches through storage backends
- Handles incremental indexing (only updates changed pages)

**TextSplitter.php**
- Splits text into token-sized chunks (configurable, typically ~1000 tokens)
- Prefers sentence boundaries using Vanderlee\Sentence
- Handles long sentences by splitting at word boundaries
- Maintains overlap between chunks (MAX_OVERLAP_LEN = 200 tokens) for context preservation

**ModelFactory.php**
- Creates and caches model instances (chat, rephrase, embedding)
- Loads model configurations from Model/*/models.json files
- Supports multiple providers: OpenAI, Gemini, Anthropic, Mistral, Ollama, Groq, Reka, VoyageAI

### Model System

**Model/AbstractModel.php**
- Base class for all LLM implementations
- Handles API communication with retry logic (MAX_RETRIES = 3)
- Tracks usage statistics (tokens, costs, time, requests)
- Implements debug mode for API inspection
- Uses DokuHTTPClient for HTTP requests

**Model Interfaces**
- `ChatInterface`: For conversational models (getAnswer method)
- `EmbeddingInterface`: For embedding models (getEmbedding method, getDimensions method)
- `ModelInterface`: Base interface with token limits and pricing info

**Model Providers**
Each provider has its own namespace under Model/:
- OpenAI/, Gemini/, Anthropic/, Mistral/, Ollama/, Groq/, Reka/, VoyageAI/
- Each contains ChatModel.php and/or EmbeddingModel.php
- Model info (token limits, pricing, dimensions) defined in models.json

### Storage Backends

**Storage/AbstractStorage.php**
- Abstract base for vector storage implementations
- Defines interface for chunk storage and similarity search

**Available Implementations:**
- SQLiteStorage: Local SQLite database
- ChromaStorage: Chroma vector database
- PineconeStorage: Pinecone cloud service
- QdrantStorage: Qdrant vector database

### Data Flow

1. **Indexing**: Pages → TextSplitter → Chunks → EmbeddingModel → Vector Storage
2. **Querying**: Question → EmbeddingModel → Vector → Storage.getSimilarChunks() → Filtered Chunks
3. **Chat**: Question + History + Context Chunks → ChatModel → Answer

### Key Features

**Question Rephrasing**
- Converts follow-up questions into standalone questions using chat history
- Controlled by `rephraseHistory` config (number of history entries to use)
- Only applied when rephraseHistory > chatHistory to avoid redundancy

**Context Management**
- Chunks include breadcrumb trail (namespace hierarchy + page title)
- Token counting uses tiktoken-php for accurate limits
- Respects model's max input token length
- Filters chunks by ACL permissions and similarity threshold

**Language Support**
- `preferUIlanguage` setting controls language behavior:
  - LANG_AUTO_ALL: Auto-detect from question
  - LANG_UI_ALL: Always use UI language
  - LANG_UI_LIMITED: Use UI language and limit sources to that language

### AJAX Integration

**action.php**
- Handles `AJAX_CALL_UNKNOWN` event for 'aichat' calls
- Processes questions with chat history
- Returns JSON with answer (as rendered Markdown), sources, and similarity scores
- Implements access restrictions via helper->userMayAccess()
- Optional logging of all interactions

### Frontend
- **script/**: JavaScript for UI integration
- **syntax/**: DokuWiki syntax components
- **renderer.php**: Custom renderer for AI chat output

## Configuration

Plugin configuration is in `conf/`:
- **default.php**: Default config values
- **metadata.php**: Config field definitions and validation

Key settings:
- Model selection: chatmodel, rephrasemodel, embedmodel
- Storage: storage backend type
- API keys: openai_apikey, gemini_apikey, etc.
- Chunk settings: chunkSize, contextChunks, similarityThreshold
- History: chatHistory, rephraseHistory
- Access: restrict (user/group restrictions)
- Indexing filters: skipRegex, matchRegex

## Testing

Tests are in `_test/` directory:
- Extends DokuWikiTest base class
- Uses @group plugin_aichat annotation

## Important Implementation Notes

- All token counting uses TikToken encoder for rough estimates
- Chunk IDs are calculated as: pageID * 100 + chunk_sequence (pageIDs come from DokuWiki's internal search index)
- Models are cached in ModelFactory to avoid re-initialization
- API retries use exponential backoff (sleep for retry count seconds)
- Breadcrumb trails provide context to AI without requiring full page content
- Storage backends handle similarity search differently but provide unified interface
- UTF-8 handling is critical for text splitting (uses dokuwiki\Utf8\PhpString)
