# Task: Replace Mocked `api/chat` Response with OpenAI Responses API Streaming

## Summary

Update the existing `api/chat` endpoint so it no longer returns a mocked response. The endpoint must call the OpenAI Responses API, stream the assistant response back to the client, and preserve conversation context across repeated requests by using OpenAI Conversations API.

## Endpoint

`POST api/chat`

## Goal

When a user sends a chat message, the backend should:

1. Resolve the current user session.
2. Find or create an OpenAI `conversation_id` for that session.
3. Send the user message to OpenAI through the Responses API with streaming enabled.
4. Forward the OpenAI stream to the frontend in real time.
5. Reuse the same `conversation_id` on future requests from the same session so OpenAI has the previous context.

## Background

The endpoint already exists and currently returns a mocked answer. This mock behavior must be replaced with a real OpenAI integration.

OpenAI Conversations should be used as the durable context container. The local application should not manually rebuild the full message history on each request. Instead, it should store a mapping between the application session and the OpenAI conversation ID.

## Requirements

### 1. Replace Mock Response

Remove the current mocked response from `api/chat`.

The endpoint must call:

- `POST /v1/responses` for generating assistant responses.
- `POST /v1/conversations` when a new conversation needs to be created.

### 2. Streaming Response

The endpoint must return the assistant response as a stream.

Expected behavior:

- The frontend receives partial assistant output as soon as OpenAI emits it.
- The backend must not wait for the full OpenAI response before sending data to the client.
- The response should be compatible with the current frontend streaming implementation.
- If the frontend expects Server-Sent Events, return `Content-Type: text/event-stream`.
- If the frontend expects raw text chunks, keep the existing frontend-compatible stream format.


The exact model name should be configurable via environment variable.

### 3. Session to Conversation Mapping

Add a separate database table to store the relation between the application session and the OpenAI conversation.

Suggested table name:

`chat_conversations`

Suggested schema:

| Column | Type | Required | Notes |
|---|---:|:---:|---|
| `id` | bigint / uuid | yes | Primary key |
| `session_id` | string / uuid | yes | Application session identifier |
| `conversation_id` | string | yes | OpenAI Conversation ID, for example `conv_xxx` |
| `created_at` | timestamp | yes | Record creation time |
| `updated_at` | timestamp | yes | Last update time |

Indexes / constraints:

- Unique index on `session_id`.
- Index on `conversation_id`.

### 4. Conversation Resolution Flow

For each request to `api/chat`:

1. Read the current `session_id`.
2. Search `chat_conversations` by `session_id`.
3. If a record exists:
    - Use the stored `conversation_id`.
4. If no record exists:
    - Create a new OpenAI conversation via `POST /v1/conversations`.
    - Store `session_id` and returned `conversation_id` in `chat_conversations`.
    - Use that `conversation_id` for the response request.

### 5. Request Validation

The endpoint should validate that the request contains a non-empty user message.

Validation rules:

- `message` is required.
- `message` must be a string.
- `message` must not be empty after trimming.

### 6. Error Handling

Handle the following cases:

- Missing or invalid message.
- Missing OpenAI API key.
- Failed OpenAI conversation creation.
- Failed OpenAI response creation.
- Stream interruption from OpenAI.
- Database failure while reading or writing `chat_conversations`.

Expected behavior:

- Return `400` for invalid client input.
- Return `500` for internal configuration or database errors.
- Return `502` or `503` for OpenAI upstream errors.
- Log upstream errors without exposing sensitive details to the frontend.

### 7. Configuration

Add environment variables:

```env
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
OPENAI_BASE_URL=https://api.openai.com/v1
```

The API key must never be exposed to the frontend.

### 8. Security Notes

- Do not accept `conversation_id` directly from the client as the source of truth.
- Conversation ownership must be determined by the server-side session.
- A user must not be able to access another session's conversation by guessing or passing a conversation ID.
- Logs must not contain the OpenAI API key.

### 9. Acceptance Criteria

- `api/chat` no longer returns a mocked response.
- `api/chat` streams assistant output from OpenAI to the frontend.
- A new session creates exactly one OpenAI conversation record.
- A repeated request from the same session reuses the existing `conversation_id`.
- The assistant can answer using previous context from the same session.
- The `chat_conversations` table exists with a unique `session_id` mapping.
- Errors are handled with appropriate HTTP status codes.
- OpenAI API key and conversation IDs are handled only on the backend.

## Implementation Notes

### OpenAI Conversation Creation

The backend should parse OpenAI streaming events and forward only frontend-relevant text deltas unless the current frontend already expects raw OpenAI SSE events.

