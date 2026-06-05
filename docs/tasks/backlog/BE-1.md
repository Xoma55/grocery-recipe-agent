BE-1

Status: Completed

# Create Minimal Symfony Backend Skeleton with Chat API Endpoint

## Description

Set up a minimal Symfony application using Composer (`composer create-project`) that will serve as the backend foundation for future OpenAI integrations.

The application must expose a single API endpoint:

* `POST /api/chat`

At this stage, the endpoint should return a stubbed JSON response without calling the OpenAI API.

Configuration values for the OpenAI integration must be loaded from environment variables defined in `.env.local`:

* `OPENAI_API_KEY`
* `OPENAI_MODEL`

Install and configure `nelmio/cors-bundle` to allow requests from the frontend development origin.

The implementation should follow Symfony best practices and ensure that sensitive credentials are never hardcoded in the source code.

## Acceptance Criteria

### Application Setup

* Symfony application is created using `composer create-project`.
* Application starts successfully in the local development environment.
* No runtime errors occur during startup.

### Chat Endpoint

* A controller is implemented with a single route: `POST /api/chat`.
* Sending a POST request to `/api/chat` returns HTTP 200.
* Response is valid JSON.
* Response contains a stubbed payload (e.g. `{ "message": "Chat endpoint is ready" }`).
* No OpenAI API calls are performed.

### Environment Configuration

* `OPENAI_API_KEY` is read from `.env.local`.
* `OPENAI_MODEL` is read from `.env.local`.
* Values are accessible through Symfony configuration or services.
* No API keys or model names are hardcoded in the codebase.

### CORS Configuration

* `nelmio/cors-bundle` is installed and configured.
* Requests from the frontend development origin are allowed.
* Browser preflight (`OPTIONS`) requests are handled successfully.
* Cross-origin POST requests to `/api/chat` succeed from the configured frontend origin.
* Requests from unauthorized origins are rejected.

### Verification

* Application boots successfully.
* Environment variables are loaded correctly.
* `POST /api/chat` returns the expected stubbed JSON response.
* Frontend requests from the configured development origin are accepted via CORS.
