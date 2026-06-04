# Database

## Database

SQLite 3

## Purpose

SQLite is used as the primary application database.

## Rules

- UUID is used as the primary key for all business entities.
- All schema changes must be implemented through Doctrine migrations.
- Foreign keys must be enabled and enforced.
- Avoid database-specific features that make migration to PostgreSQL difficult.
- Prefer application-level validation for complex constraints.
- Use transactions for multi-step write operations.

## Source of Truth

1. Doctrine entities
2. Doctrine migrations

Documentation is secondary.

## SQLite Specific Rules

- Ensure foreign_keys=ON is enabled.
- Store UUIDs as TEXT.
- Store dates and timestamps as DATETIME strings.
- Do not rely on ENUM types.
- Do not rely on JSON-specific database features.
- Avoid raw SQL unless necessary.

## Naming Conventions

Tables:
snake_case

Columns:
snake_case

Indexes:
idx_<table>_<column>

Foreign Keys:
fk_<table>_<reference>

Unique Constraints:
uniq_<table>_<column>

## Migrations

Every schema change must include:

- Doctrine migration
- Entity update
- Related tests if applicable

Never modify the database manually.

## Performance Guidelines

- Add indexes for frequently filtered columns.
- Avoid N+1 queries.
- Prefer pagination for large datasets.
- Review Doctrine relations carefully to prevent excessive lazy loading.

## Common Entity Fields

Recommended fields:

id
created_at
updated_at

Optional:

deleted_at
version

depending on domain requirements.