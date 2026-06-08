# Add GPT-5.5 Model Configuration Parameters to Application Settings

## Description

The application currently uses hardcoded GPT model request parameters. These parameters should be moved to configuration and loaded from the `.env` file.
The configured values must be applied when building requests to the OpenAI Responses API.

## Required Parameters

Add support for the following environment variables:

```env
OPENAI_REASONING_EFFORT=medium
OPENAI_TEXT_VERBOSITY=medium
OPENAI_MAX_OUTPUT_TOKENS=4000
```

### Allowed Values

#### OPENAI_REASONING_EFFORT

Supported values:

* minimal
* low
* medium
* high

#### OPENAI_TEXT_VERBOSITY

Supported values:

* low
* medium
* high

#### OPENAI_MAX_OUTPUT_TOKENS

Positive integer value.

## Implementation Requirements

### 1. Configuration

* Add the new variables to `.env`.
* Load the variables from `.env`.
* Define default values in case the variables are not provided:

```text
OPENAI_REASONING_EFFORT=medium
OPENAI_TEXT_VERBOSITY=medium
OPENAI_MAX_OUTPUT_TOKENS=4000
```

### 2. Responses API Request Configuration

Update the Responses API request builder to include the configured values:

The values must be read from configuration rather than hardcoded in the request.

### 3. Validation

Implement configuration validation:

#### OPENAI_REASONING_EFFORT

Allowed values:

* minimal
* low
* medium
* high

#### OPENAI_TEXT_VERBOSITY

Allowed values:

* low
* medium
* high

#### OPENAI_MAX_OUTPUT_TOKENS

Must be a positive integer.

If invalid values are provided, the application should either:

* log a clear configuration error and fall back to default values; or
* fail startup, depending on the project's existing configuration validation approach.

## Acceptance Criteria

* New variables are added to `.env`.
* Variables are loaded from `.env`.
* Configuration values are passed to every Responses API request.
* Validation is implemented for all new parameters.
* Default values are applied when variables are missing.
* Existing configuration tests are updated (or new tests are added if applicable).
* Configuration documentation is updated.
