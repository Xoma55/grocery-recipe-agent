2026-06-05: BE-1 completed.

- Created a minimal Symfony backend skeleton under `backend/` with Composer.
- Added `POST /api/chat`, returning `{"message":"Chat endpoint is ready"}` without OpenAI calls.
- Added OpenAI env access through Symfony parameters and `App\Infrastructure\OpenAi\OpenAiConfiguration`.
- Installed and configured `nelmio/cors-bundle` for `/api/` with `CORS_ALLOW_ORIGIN` from `.env`.

2026-06-05: BE-2 completed.

- Added `App\Infrastructure\PilotConfiguration\PilotConfigurationService`.
- The service loads `CONFIG_PATH`, validates the per-pilot JSON, and returns a safe default for missing, unreadable, malformed, or invalid configuration.
- Validation covers required fields, ISO dates, `valid_from <= valid_to`, event date structure, unique SKU IDs, max 30 promoted SKUs, numeric non-negative prices, boolean promo flags, and forbidden secret-like keys.
- Registered the service in Symfony DI and added the default `CONFIG_PATH=backend/config/intermarche-lyon.json` env value.

2026-06-07: BE-3 completed.

- Added `App\Infrastructure\SystemPrompt\SystemPromptAssemblyService`.
- The service reads `SYSTEM_PROMPT_PATH`, loads `backend/config/prompt.md`, and replaces `{{ASSISTANT_NAME}}`, `{{STORE_NAME}}`, `{{EVENT_CONTEXT}}`, `{{PROMOTED_SKUS}}`, and `{{DEFAULT_SERVINGS}}`.
- Prompt values come from `PilotConfigurationService`; events and promoted SKUs are inserted as pretty JSON for AI-readable structured context.
- Registered the service in Symfony DI as public for direct resolution and added the default `SYSTEM_PROMPT_PATH=backend/config/prompt.md` env value.

2026-06-08: BE-7 completed.

- Added database-backed anonymous session management for every main HTTP request via `DatabaseSessionSubscriber`.
- Sessions are stored in SQLite using the `sessions` table from `backend/migrations/001_create_sessions.sql`.
- Added `SESSION_LIFETIME` configuration and reused the application `DATABASE_URL`; expiration is enforced on every request and expired records are deleted before replacement.
- Added dependency-free automated coverage in `backend/tests/run.php`, runnable with `composer test`.
- Added endpoint-level regression coverage proving an expired session cookie deletes the old database record before returning a replacement cookie.

2026-06-08: BE-4 completed.

- Extracted `/api/chat` request handling from `ChatController` into `App\UI\Chat\ChatRequestHandler`.
- `ChatController` is now a thin HTTP controller that only delegates the request and returns the handler response.
- Preserved existing message validation, session checks, OpenAI conversation resolution, prompt assembly, SSE streaming format, and error responses.

2026-06-08: Environment override cleanup.

- Removed project references to the local override env file; local backend configuration now uses `backend/.env` directly.
- Confirmed the local override env file is absent and no references to it remain outside ignored vendor/cache/git paths.

2026-06-08: BE-8 completed.

- Added GPT-5.5 Responses API request configuration for `OPENAI_REASONING_EFFORT`, `OPENAI_TEXT_VERBOSITY`, and `OPENAI_MAX_OUTPUT_TOKENS`.
- Values are loaded through Symfony parameters with defaults of `medium`, `medium`, and `4000`.
- `OpenAiConfiguration` validates allowed reasoning effort, text verbosity, and positive integer max output tokens with clear fail-fast errors.
- `/responses` request bodies now include configured `reasoning.effort`, `text.verbosity`, and `max_output_tokens` values.
- Added regression coverage for defaults, valid configured values, invalid configuration failures, and generated Responses API payloads.
