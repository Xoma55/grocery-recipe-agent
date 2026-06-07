BE-3

Status: Completed on 2026-06-07.

## Task: Build System Prompt Assembly Service

### Description

Create a separate DI-accessible service that assembles the final system prompt from the base prompt document `04`.

The service must take the base prompt containing the placeholders:

* `{{ASSISTANT_NAME}}`
* `{{STORE_NAME}}`
* `{{EVENT_CONTEXT}}`
* `{{PROMOTED_SKUS}}`
* `{{DEFAULT_SERVINGS}}`

and replace each placeholder with the matching value from the existing `ConfigService`.

The result should be one fully assembled system-prompt string.

### Requirements

* Create a separate service that can be resolved through DI.
* The service must use the existing `ConfigService`.
* The service must read all placeholder values from configuration.
* If a placeholder value is not currently available in config, add a corresponding `.env` variable and expose it through config.
* Implement simple find-and-replace logic for all supported placeholders.
* `{{EVENT_CONTEXT}}` and `{{PROMOTED_SKUS}}` must be inserted in an AI-readable format, such as structured plain text or JSON-like content.
* The service must expose a method available through DI that returns the fully assembled prompt.

### Suggested Method

```php
getAssembledSystemPrompt(): string
```

### Acceptance Criteria

* Given a sample config, the service returns one assembled prompt string.
* Every supported placeholder is filled.
* The result is verified against the expected output for the sample config.
* `EVENT_CONTEXT` and `PROMOTED_SKUS` are formatted in a way that is clear and understandable for AI.
* Missing config values are backed by `.env` variables and exposed through `ConfigService`.

```
```
