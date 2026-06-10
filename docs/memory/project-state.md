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

2026-06-08: BE-9 completed.

- Added `GET /api/chat/history` for loading message history for the current anonymous session.
- The endpoint reads `conversation_id` from `chat_conversations` by current `session_id`; it does not accept or trust client-provided conversation IDs.
- Missing session-to-conversation mapping returns `{"messages":[]}` without creating a new OpenAI conversation.
- Added OpenAI Conversations API item listing through `GET /conversations/{conversation_id}/items` and normalizes user/assistant text messages for the frontend.
- Added regression coverage for empty history, existing history, session isolation, and OpenAI upstream failure handling.

2026-06-10: BE-10 completed.

- Added `POST /api/chat/reset` for starting a new chat without resetting the anonymous application session.
- The endpoint deletes only the current session's `chat_conversations` row, ignores client-provided conversation IDs, and returns `204 No Content`.
- Reset does not call OpenAI; the next chat message recreates a new OpenAI conversation through the existing resolver flow.
- Added regression coverage for mapping deletion, idempotent reset, session and cookie preservation, session isolation, empty history after reset, missing session handling, and next-message conversation recreation.

2026-06-10: API documentation added.

- Created `docs/context/api.md` documenting backend API endpoints: `POST /api/chat`, `GET /api/chat/history`, and `POST /api/chat/reset`.
- Documented anonymous session handling through the `grocery_session` cookie, including request cookie examples, `Set-Cookie` behavior, expiration, missing/expired session replacement, and `credentials: 'include'` browser usage.
- Captured endpoint request/response shapes, SSE events, status codes, error formats, and session-scoped conversation ownership rules.

2026-06-10: FE-1 completed.

- Initialized a Next.js 16 TypeScript frontend application under `frontend/`.
- Enabled strict TypeScript and created the baseline Feature-Sliced Design structure: `app`, `widgets`, `features`, `entities`, `shared`, and `shared/api`.
- Added React Query, Zod, and react-hook-form dependencies without adding a global client-side state library.
- Added `frontend/.env` with `FRONTEND_PORT` and `NEXT_PUBLIC_BACKEND_API_URL`.
- Added a `npm run dev` startup script that reads `FRONTEND_PORT` from the environment or `frontend/.env`.
- Added shared API configuration and client helpers that centralize `NEXT_PUBLIC_BACKEND_API_URL` usage and include backend session credentials by default.

2026-06-10: FE-2 completed.

- Replaced the frontend home page with the shopper-facing chat interface.
- Added typed chat API helpers for `GET /chat/history`, `POST /chat`, and `POST /chat/reset` through the shared API layer with browser credentials preserved by `apiRequest`.
- Implemented history loading, empty/loading/error states, optimistic user messages, SSE `delta`/`done`/`error` handling, duplicate-send prevention, and reset behavior without reading or writing the `grocery_session` cookie.
- Added a responsive mobile-first chat layout with a visible top `New chat` action, scrollable message bubbles, pinned composer, keyboard submission, accessible labels, and focus restoration after send/reset.

2026-06-10: CORS credentialed chat API fix.

- Updated backend CORS configuration for `/api/` to allow credentialed cross-origin requests.
- Added `GET` to allowed CORS methods so `GET /api/chat/history` works from the frontend.
- Kept frontend requests centralized through `apiRequest`, which sends browser credentials by default without reading or writing the `grocery_session` cookie.
