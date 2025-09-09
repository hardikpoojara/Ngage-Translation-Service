# Ngage Translation Service

A Laravel-based microservice that translates structured content fields (such as name, title, description) into target languages using an OpenAI-backed connector. This README explains the project structure, how major components interact, and how to run tests.

## Project Structure Overview

- **app/**
    - **Enums/**
        - `Language.php`: Enum of supported languages. Provides code mapping, labels, default language, and utilities like `tryFromCode` / `nameFor`.
    - **Events/**: Domain events that can be dispatched within the app.
    - **Filters/**: Request/Resource filters used in repositories/controllers.
    - **Http/**
        - `Controllers/`, `Middleware/`, `Requests/`, `Resources/`: Web/API layer (if applicable to this service).
    - **Jobs/**
        - `TranslateContentJob.php`: Queue job that delegates translation work to the `TranslationService`.
    - **Models/**: Eloquent models, e.g., `ContentTranslation`.
    - **Providers/**: Laravel service providers and bootstrapping logic.
    - **Repositories/**: Persistence and query abstractions.
    - **Services/**
        - **OpenAI/**: Client, connector, and request classes to interact with OpenAI.
            - `OpenAIConnector.php`: Sends requests to OpenAI.
            - `Requests/ChatCompletionRequest.php`: Wraps prompt payload for chat completions.
        - `TranslationService.php`: Core domain service that builds translation prompts, calls `OpenAIConnector`, and parses responses. Includes robust fallbacks for non-JSON outputs.

- **database/**
    - `migrations/`: Database schema migrations. Example: `create_content_translations_table` migration.
- **routes/**: API/routes.
- **tests/**
    - **Unit/**
        - `TranslationServiceTest.php`: Unit tests for TranslationService behavior (success, non-JSON fallback, request exception wrapping).
        - `TranslateContentJobTest.php`: Tests the queue job integration with the service.

---

## How the Translation Flow Works

1. A caller (controller, job, or command) invokes `TranslationService::translateContent` with an associative array of fields and a target language (Language enum or code string like `"es"`).
2. `TranslationService` builds a prompt geared toward JSON-only responses and sends it via `OpenAIConnector` using `ChatCompletionRequest`.
3. The service attempts to parse the response content as JSON. If JSON parsing fails, it falls back to a line-based parser to extract fields (name, title, description) from plain text.
4. The method returns an array with translated fields, preserving structure and falling back to original content when necessary.

---

## Key Files to Review

- `app/Services/TranslationService.php`
- `app/Services/OpenAI/OpenAIConnector.php`
- `app/Services/OpenAI/Requests/ChatCompletionRequest.php`
- `app/Jobs/TranslateContentJob.php`
- `app/Enums/Language.php`
- `tests/Unit/*`

---

## Installation

**Prerequisites:**

- PHP 8.2+ with required extensions (ctype, mbstring, openssl, pdo, tokenizer, xml, curl)
- Composer 2.x
- A database (SQLite/MySQL/PostgreSQL) if you plan to persist data
- Node.js 18+ (only if you will build frontend assets)

**Steps (fresh clone):**

1. Clone the repo:
```bash
git clone <repo-url> && cd ngage-translation-service
```
2. Install PHP dependencies
```bash
composer install
```
3. Copy environment file
```bash
cp .env.example .env
```
4. Generate application key
```bash
php artisan key:generate
```
5. Configure .env
```bash
OPENAI_API_KEY=your_openai_api_key
```
6. Run migrations
```bash
php artisan migrate
```
7. Run queue worker (if using queued jobs with Redis)
```bash
php artisan queue:work
```


**Sample Request:**
```bash
curl --location 'http://localhost:8000/api/translations' \
--form 'name="Harrison"' \
--form 'title="full-range hard"' \
--form 'description="Wooden Plastic navigate multi-tasking Minnesota"' \
--form 'target_language="pt"' \
--form 'source_language="en"'
```
