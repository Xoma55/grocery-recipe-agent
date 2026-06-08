
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

## OpenAI Configuration

- `OPENAI_API_KEY`: OpenAI API key.
- `OPENAI_MODEL`: Responses API model, defaults to `gpt-4.1-mini`.
- `OPENAI_BASE_URL`: OpenAI API base URL, defaults to `https://api.openai.com/v1`.
- `OPENAI_REASONING_EFFORT`: Responses API reasoning effort. Allowed values: `minimal`, `low`, `medium`, `high`. Defaults to `medium`.
- `OPENAI_TEXT_VERBOSITY`: Responses API text verbosity. Allowed values: `low`, `medium`, `high`. Defaults to `medium`.
- `OPENAI_MAX_OUTPUT_TOKENS`: Responses API max output tokens. Must be a positive integer. Defaults to `4000`.

Persistence Rules
- Doctrine is the only persistence layer.
- Repositories encapsulate all database access.
- Controllers and handlers must not use EntityManager directly.
