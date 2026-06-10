# Backend API

This document describes the HTTP API exposed by the Symfony backend.

## Base Path

All application endpoints are served under `/api`.

## Session Handling

The backend uses an anonymous application session for every main HTTP request.

- Session cookie name: `grocery_session`
- Example request cookie: `grocery_session=c8f06e528587816a4363aaae874d2550c18579b6489cde96d926e011a924410e`
- Cookie path: `/`
- SameSite policy: `Lax`
- HttpOnly: `true`
- Secure: auto-detected by Symfony because the backend passes `null` for the secure flag
- Expiration: controlled by `SESSION_LIFETIME`

Session resolution rules:

1. If the request has no `grocery_session` cookie, the backend creates a new session and returns `Set-Cookie`.
2. If the cookie points to an active stored session, the backend reuses that session and returns the same session cookie value.
3. If the cookie points to a missing or expired session, the backend creates a replacement session and returns a replacement `Set-Cookie` value.
4. Expired session records are deleted before the replacement session is stored.

Browser clients must include credentials on API requests so the browser sends and updates the session cookie. Because the cookie is `HttpOnly`, frontend code must not try to read or write it manually.

```js
fetch('/api/chat/history', {
  method: 'GET',
  credentials: 'include',
});
```

For cross-origin frontend development, the request origin must match `CORS_ALLOW_ORIGIN`. The backend CORS configuration currently allows credentialed requests with `Content-Type` and `Authorization` headers and `OPTIONS` / `GET` / `POST` methods for `/api/` preflight requests.

## Error Format

JSON error responses use this shape:

```json
{
  "error": "Internal server error"
}
```

OpenAI upstream failures are returned as:

```json
{
  "error": "OpenAI upstream error"
}
```

Internal errors do not expose OpenAI keys, raw upstream bodies, database details, or stack traces to the client.

## `POST /api/chat`

Streams an assistant response for the current anonymous session.

### Request

Headers:

```http
Content-Type: application/json
Cookie: grocery_session=c8f06e528587816a4363aaae874d2550c18579b6489cde96d926e011a924410e
```

Body:

```json
{
  "message": "Je veux cuisiner avec des tomates et des oeufs."
}
```

The `message` field must be a non-empty string after trimming. If JSON decoding does not produce an object, the handler also checks form field `message`.

### Successful Response

Status: `200 OK`

Headers:

```http
Content-Type: text/event-stream
Cache-Control: no-cache
X-Accel-Buffering: no
Set-Cookie: grocery_session=...
```

The response is Server-Sent Events.

Text chunks are emitted as `delta` events:

```text
event: delta
data: {"delta":"Bonjour"}
```

The stream finishes with a `done` event:

```text
event: done
data: {"done":true}
```

If streaming is interrupted after the response has started, the stream emits an `error` event:

```text
event: error
data: {"error":"OpenAI stream interrupted"}
```

### Session and Conversation Behavior

- The backend resolves ownership from the `grocery_session` cookie only.
- The client does not send or control an OpenAI conversation id.
- If the current session already has a local `chat_conversations` mapping, that OpenAI conversation is reused.
- If no mapping exists, the backend creates a new OpenAI conversation and stores the mapping for the current session.
- The request uses the assembled system prompt and sends the user message to the OpenAI Responses API with streaming enabled.

### Error Responses

- `400 Bad Request`: `message` is missing, not a string, or empty after trimming.
- `502 Bad Gateway`: OpenAI returns a non-2xx client-side upstream error.
- `503 Service Unavailable`: OpenAI connection fails or OpenAI returns a 5xx upstream error.
- `500 Internal Server Error`: session resolution or preparation fails internally.

## `GET /api/chat/history`

Loads normalized chat history for the current anonymous session.

### Request

Headers:

```http
Cookie: grocery_session=c8f06e528587816a4363aaae874d2550c18579b6489cde96d926e011a924410e
```

No request body is used.

Client-provided `conversation_id` values are ignored. Conversation ownership is determined only from the current backend session.

### Successful Response

Status: `200 OK`

When the current session has no conversation mapping:

```json
{
  "messages": []
}
```

When history exists:

```json
{
  "messages": [
    {
      "role": "user",
      "content": "Je veux cuisiner avec des tomates et des oeufs."
    },
    {
      "role": "assistant",
      "content": "Voici une idee de repas..."
    }
  ]
}
```

Only OpenAI conversation items with role `user` or `assistant` and non-empty text content are returned.

### Error Responses

- `502 Bad Gateway`: OpenAI returns a non-2xx client-side upstream error while loading history.
- `503 Service Unavailable`: OpenAI connection fails or OpenAI returns a 5xx upstream error while loading history.
- `500 Internal Server Error`: session resolution, database lookup, or history normalization fails internally.

## `POST /api/chat/reset`

Starts a new chat for the current anonymous session without replacing the application session.

### Request

Headers:

```http
Cookie: grocery_session=c8f06e528587816a4363aaae874d2550c18579b6489cde96d926e011a924410e
```

No request body is used.

Client-provided `conversation_id` values in query parameters, headers, or body are ignored.

### Successful Response

Status: `204 No Content`

Body: empty

The response still receives the normal session cookie from the backend session subscriber.

```http
Set-Cookie: grocery_session=c8f06e528587816a4363aaae874d2550c18579b6489cde96d926e011a924410e; path=/; httponly; samesite=lax
```

### Session and Conversation Behavior

- Deletes only the `chat_conversations` row for the current session id.
- Does not delete, replace, clear, or expire the `sessions` row.
- Does not clear or replace the `grocery_session` cookie when the current session is active.
- Does not call OpenAI.
- Safe to call repeatedly; if no mapping exists, the endpoint still returns `204 No Content`.
- After reset, `GET /api/chat/history` for the same session returns `{"messages":[]}`.
- The next `POST /api/chat` for the same session creates a new OpenAI conversation mapping.

### Error Responses

- `500 Internal Server Error`: session resolution is missing internally or deleting the local conversation mapping fails.
