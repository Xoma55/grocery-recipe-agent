2026-06-05: BE-1 completed.

- Created a minimal Symfony backend skeleton under `backend/` with Composer.
- Added `POST /api/chat`, returning `{"message":"Chat endpoint is ready"}` without OpenAI calls.
- Added OpenAI env access through Symfony parameters and `App\Infrastructure\OpenAi\OpenAiConfiguration`.
- Installed and configured `nelmio/cors-bundle` for `/api/` with `CORS_ALLOW_ORIGIN` from `.env.local`.
