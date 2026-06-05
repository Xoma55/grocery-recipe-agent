2026-06-05: BE-1 completed.

- Created a minimal Symfony backend skeleton under `backend/` with Composer.
- Added `POST /api/chat`, returning `{"message":"Chat endpoint is ready"}` without OpenAI calls.
- Added OpenAI env access through Symfony parameters and `App\Infrastructure\OpenAi\OpenAiConfiguration`.
- Installed and configured `nelmio/cors-bundle` for `/api/` with `CORS_ALLOW_ORIGIN` from `.env.local`.

2026-06-05: BE-2 completed.

- Added `App\Infrastructure\PilotConfiguration\PilotConfigurationService`.
- The service loads `CONFIG_PATH`, validates the per-pilot JSON, and returns a safe default for missing, unreadable, malformed, or invalid configuration.
- Validation covers required fields, ISO dates, `valid_from <= valid_to`, event date structure, unique SKU IDs, max 30 promoted SKUs, numeric non-negative prices, boolean promo flags, and forbidden secret-like keys.
- Registered the service in Symfony DI and added the default `CONFIG_PATH=backend/config/intermarche-lyon.json` env value.
