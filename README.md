# Grocery Recipe Agent

Anonymous grocery recipe assistant for an in-store shopper. The app suggests one practical meal in French, streams the answer, and lists the few extra ingredients to grab.

## Backend

The backend lives in `backend/`.

- Symfony HTTP API under `/api`
- Anonymous sessions stored in SQLite through the `grocery_session` cookie
- Chat endpoints for streaming, history, and reset
- OpenAI Responses API integration
- Pilot configuration and system prompt assembly from `backend/config/`

Main endpoints:

- `POST /api/chat`
- `GET /api/chat/history`
- `POST /api/chat/reset`

## Frontend

The frontend lives in `frontend/`.

- Next.js 16 with TypeScript
- Feature-Sliced Design structure under `frontend/src/`
- React Query for server state
- Shared API client in `frontend/src/shared/api/`
- Mobile-first chat UI that preserves backend session cookies with credentialed requests

## Development Setup

Requirements:

- PHP `>=8.4`
- Composer
- Node.js and npm
- SQLite CLI

Install dependencies:

```bash
cd backend
composer install

cd ../frontend
npm install
```

Configure environment files:

- Backend: edit `backend/.env` and set `OPENAI_API_KEY`; keep `DATABASE_URL`, `CONFIG_PATH`, `SYSTEM_PROMPT_PATH`, and `CORS_ALLOW_ORIGIN` valid for local development.
- Frontend: edit `frontend/.env`; by default `FRONTEND_PORT=3005` and `NEXT_PUBLIC_BACKEND_API_URL=http://localhost:8000/api`.

Create the local SQLite schema:

```bash
cd backend
sqlite3 var/app.sqlite < migrations/001_create_sessions.sql
sqlite3 var/app.sqlite < migrations/002_create_chat_conversations.sql
```

Run the backend in one terminal:

```bash
cd backend
php -S localhost:8000 -t public public/index.php
```

Run the frontend in another terminal:

```bash
cd frontend
npm run dev
```

Open the frontend at `http://localhost:3005`.

## Useful Commands

Backend tests:

```bash
cd backend
composer test
```

Frontend type check:

```bash
cd frontend
npm run typecheck
```
