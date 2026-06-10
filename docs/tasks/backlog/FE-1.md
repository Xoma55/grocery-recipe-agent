# Initialize Frontend Framework

Status: Completed

## Summary

Initialize the frontend application in `frontend/` using the project frontend stack and prepare it for local development against the existing backend API.

The frontend must be configured through environment variables, including the development server port and the backend API base URL.

## Goal

Create a clean frontend foundation that can run locally, call the Symfony backend, and support the upcoming shopper-facing recipe chat experience.

## Scope

- Initialize a Next.js 16 application in `frontend/`.
- Configure TypeScript strict mode.
- Prepare the Feature-Sliced Design folder structure.
- Add required frontend dependencies for server state and validation.
- Configure local environment variables for the frontend port and backend API path.
- Keep the initial UI minimal; this task is framework setup, not chat UI implementation.

## Requirements

### 1. Framework Setup

- The frontend application is created under `frontend/`.
- The application uses Next.js 16 with TypeScript.
- TypeScript strict mode is enabled.
- The application can start in local development mode without runtime errors.

### 2. Project Structure

Create the baseline Feature-Sliced Design structure under `frontend/src/`:

- `app/`
- `widgets/`
- `features/`
- `entities/`
- `shared/`
- `shared/api/`

All future API access must go through `shared/api/`.

### 3. Dependencies

Install and configure the frontend dependencies required by the project architecture:

- React Query for server state.
- Zod for runtime validation.
- react-hook-form for forms.

No global client-side state library should be added in this task.

### 4. Environment Configuration

Add frontend environment configuration in `frontend/.env`.

The file must define:

- `FRONTEND_PORT` for the local frontend development server port.
- `NEXT_PUBLIC_BACKEND_API_URL` for the backend API base URL.

The development start command must read `FRONTEND_PORT` from the environment instead of hardcoding the port in source code.

Frontend API code must read the backend API base URL from `NEXT_PUBLIC_BACKEND_API_URL` instead of hardcoding the backend path in source code.

### 5. Backend Connectivity Preparation

Add a minimal API client foundation under `frontend/src/shared/api/` that centralizes backend URL usage.

The API client does not need to implement chat behavior in this task, but it must make future calls use the configured backend URL consistently.

### 6. Documentation

Update relevant frontend documentation if needed so a developer can run the frontend locally and understand which environment variables are required.

## Acceptance Criteria

- `frontend/` contains a Next.js 16 TypeScript application.
- TypeScript strict mode is enabled.
- `frontend/src/` contains the baseline Feature-Sliced Design folders listed in this task.
- React Query is installed and available for future server-state integration.
- Zod is installed and available for runtime validation.
- react-hook-form is installed and available for future forms.
- `frontend/.env` defines `FRONTEND_PORT`.
- `frontend/.env` defines `NEXT_PUBLIC_BACKEND_API_URL`.
- The frontend development server starts on the port configured by `FRONTEND_PORT`.
- The backend API base URL is read from `NEXT_PUBLIC_BACKEND_API_URL` through shared API code.
- No backend API URL is hardcoded in frontend source files.
- No frontend port is hardcoded in frontend source files.
- The application starts locally without runtime errors.
- The default page renders successfully in a browser.
- Existing backend files are not modified except where documentation or integration instructions explicitly require it.
