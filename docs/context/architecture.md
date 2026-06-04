# Architecture

## Tech Stack

Backend:
- Symfony 8.0
- PHP 8.5
- Doctrine ORM
- SQLite

Frontend:
- Next.js 16
- TypeScript
- React Query
- Zod

## Architectural Style

Backend:
- Monolithic

Persistence:
Doctrine ORM + SQLite

Frontend:
- Feature-Sliced Design

## Communication

Frontend -> REST API -> Backend

## Authentication

None (Based on session)

## Principles

- Business logic belongs to Domain/Application layers
- UI layers must remain thin
- Single source of truth for data is backend