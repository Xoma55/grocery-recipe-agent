
# Backend

backend/

## Layers

src/

Domain/
Application/
Infrastructure/
UI/

## Responsibilities

Domain:
- entities
- value objects
- domain services

Application:
- commands
- queries
- handlers
- DTO

Infrastructure:
- doctrine
- external services
- repositories

UI:
- controllers
- request mapping
- response mapping

## Rules

- Controllers contain no business logic
- Doctrine entities must not be used as API responses
- Use DTOs between layers
- Business rules belong to Domain or Application
- No direct DB access outside repositories

## Testing

- PHPUnit
- Integration tests for repositories
- Functional tests for API endpoints

Persistence Rules
- Doctrine is the only persistence layer.
- Repositories encapsulate all database access.
- Controllers and handlers must not use EntityManager directly.