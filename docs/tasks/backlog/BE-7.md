# Add Database-Backed Session Management

Status: Completed on 2026-06-08.

## Overview

Implement server-side session management as the session storage backend.
The application must automatically create and manage sessions for all incoming requests. Session expiration must be configurable through an environment variable and enforced on every request.

---

## Functional Requirements

### 1. Session Initialization

A session must be created automatically when a user accesses any application endpoint.

If a valid session already exists, it should be reused.

---

### 2. Session Lifetime Configuration

Session lifetime must be configurable through an environment variable.

Example:

```env
SESSION_LIFETIME=3600
```

Where:

- `3600` = session lifetime in seconds.
- The value should be loaded from the application configuration.
- No hardcoded session timeout values are allowed.

---

### 3. Session Storage

Sessions must be stored in the SQLite database.

Create a dedicated table for session records.

Suggested structure:

| Column      | Type      | Description |
|-------------|-----------|-------------|
| id          | string    | Session identifier |
| created_at  | datetime  | Session creation timestamp |
| expired_at  | datetime  | Session expiration timestamp |

The implementation should include a database migration for creating the session table.

---

### 4. Session Validation

For every request:

1. Read the session identifier from the request.
2. Load the corresponding session record from the database.
3. Compare the current timestamp with the `expired_at` value.

#### Valid Session

If the session exists and has not expired:

- Continue processing the request.
- Reuse the existing session.

#### Expired Session

If the session exists but has expired:

- Delete the expired session record.
- Create a new session.
- Persist the new session in the database.
- Associate the new session with the current request/response lifecycle.

#### Missing Session

If no session exists:

- Create a new session.
- Persist it in the database.
- Associate it with the current request/response lifecycle.

---

### 5. Session Expiration Logic

When a new session is created:

```text
expired_at = current_timestamp + SESSION_LIFETIME
```

The expiration timestamp must be stored in the database and used for validation on subsequent requests.

---

## Acceptance Criteria

### Session Creation

- A new session is created on the first request to any endpoint.
- The session record is stored in database table.

### Session Reuse

- Requests made before expiration reuse the existing session.
- No duplicate session records are created for active sessions.

### Session Expiration

- Expired sessions are detected on incoming requests.
- Expired session records are removed from the database.
- A new session is automatically created after expiration.

### Configuration

- Session lifetime is loaded from `.env`.
- Changing the value in `.env` changes session behavior without code modifications.

### Database

- Migration successfully creates the session table.
- Session records contain a valid expiration timestamp.

### Testing

Automated tests must cover:

1. Session creation on first request.
2. Reuse of an active session.
3. Detection of an expired session.
4. Removal of an expired session.
5. Creation of a new session after expiration.
6. Reading session lifetime from configuration.
