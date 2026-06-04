# Frontend

## Architecture

Feature-Sliced Design

src/

app/
widgets/
features/
entities/
shared/

## Rules

- TypeScript strict mode
- React Query for server state
- Zod for validation
- Server Components by default
- Client Components only when necessary

## API Access

All API requests go through:

shared/api/

## Forms

Use:

- react-hook-form
- zod

## State Management

Server state:
- React Query

UI state:
- React state