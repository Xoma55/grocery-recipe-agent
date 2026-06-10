# Frontend

frontend/

## Architecture

Feature-Sliced Design

`src/`

- `app/`
- `widgets/`
- `features/`
- `entities/`
- `shared/`
- `shared/api/`

## Local Development

Install dependencies and start the frontend from `frontend/`:

```bash
npm install
npm run dev
```

Required environment variables live in `frontend/.env`:

- `FRONTEND_PORT`: local Next.js development server port.
- `NEXT_PUBLIC_BACKEND_API_URL`: backend API base URL, including `/api`.

The `dev` script reads `FRONTEND_PORT` from the environment or `frontend/.env`.

## Rules

- TypeScript strict mode
- React Query for server state
- Zod for validation
- Server Components by default
- Client Components only when necessary

## API Access

All API requests go through:

`shared/api/`

The shared API client reads `NEXT_PUBLIC_BACKEND_API_URL`; frontend source files must not hardcode the backend URL.

## Forms

Use:

- react-hook-form
- zod

## State Management

Server state:
- React Query

UI state:
- React state
