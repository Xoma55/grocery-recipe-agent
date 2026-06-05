BE-2

# Task: Implement Pilot Configuration Loading and Validation

Status: Completed on 2026-06-05.

## Description

Implement a `PilotConfigurationService` responsible for loading and validating a single pilot configuration JSON file.

The configuration file path must be provided via the `CONFIG_PATH` environment variable (defined in `.env`).

Current example:

```env
CONFIG_PATH=backend/config/intermarche-lyon.json
```

The JSON schema and structure are defined in `03-config-schema.md`.

The service must be registered in Dependency Injection (DI) and be available for injection into other services that require access to the pilot configuration.

### Requirements

#### Configuration Loading

* Read the configuration file from the path specified by `CONFIG_PATH`.
* Load and parse the JSON file during service initialization or on first access.
* No external model/API connections are required; only local file reading.

#### Validation Rules

Validate the configuration against the schema and business rules:

* All required fields are present.
* Dates are valid and correctly formatted.
* `valid_from <= valid_to`.
* SKU identifiers are unique.
* Maximum number of SKUs is 30.
* All price values are numeric.

#### Error Handling

* Invalid or malformed JSON must not crash the application.
* Validation errors should be handled gracefully.
* The service should return a safe default configuration when:

    * the file does not exist;
    * the file cannot be read;
    * the JSON is malformed;
    * validation fails.

### Technical Notes

* Create `PilotConfigurationService`.
* Register the service in DI.
* Other services should access configuration through `PilotConfigurationService`.
* Centralize all configuration loading and validation logic inside the service.

## Acceptance Criteria

* A valid configuration file is successfully loaded and exposed through `PilotConfigurationService`.
* A missing configuration file returns a safe default configuration.
* An invalid configuration returns a safe default configuration.
* Malformed JSON never crashes the application.
* Validation enforces:

    * required fields;
    * valid dates;
    * `valid_from <= valid_to`;
    * unique SKU IDs;
    * maximum of 30 SKUs;
    * numeric prices.
* `PilotConfigurationService` is available through DI and can be consumed by other services.
