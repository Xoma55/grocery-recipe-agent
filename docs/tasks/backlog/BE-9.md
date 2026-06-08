# Add Conversation Message History Endpoint

Status: Completed on 2026-06-08.

## Summary

Create a new backend endpoint that returns the message history for the current session's OpenAI conversation.

The endpoint must not accept `conversation_id` from the client. It must resolve the current application session, find the matching `conversation_id` in the `chat_conversations` table, and then fetch the message history from the OpenAI Conversations API.

## Goal

When a user opens or refreshes the chat UI, the backend should be able to return the existing conversation history for the current anonymous session.

## Endpoint

Suggested endpoint:

`GET /api/chat/history`

The final route may follow the project's existing API naming conventions.

## Requirements

### 1. Session-Based Conversation Resolution

For each request:

1. Resolve the current application `session_id`.
2. Search `chat_conversations` by `session_id`.
3. If a record exists:
   - Use its stored `conversation_id`.
4. If no record exists:
   - Return an empty history response.
   - Do not create a new OpenAI conversation only for history loading.

### 2. OpenAI Conversation History Fetching

Use the resolved `conversation_id` to fetch messages from the OpenAI Conversations API.

The implementation should call the appropriate OpenAI conversation messages endpoint for the configured `OPENAI_BASE_URL`.

The response should include only data needed by the frontend to restore the chat history.

### 3. Response Format

Return a JSON response.

Suggested shape:

```json
{
  "messages": [
    {
      "role": "user",
      "content": "Bonjour"
    },
    {
      "role": "assistant",
      "content": "Bonjour, que souhaitez-vous cuisiner ce soir ?"
    }
  ]
}
```

The final shape may be adjusted to match the frontend's existing message model, but it must be stable and documented in tests.

### 4. Security

- Do not accept `conversation_id` from query parameters, request body, or headers.
- Conversation ownership must be determined only by the server-side session.
- A user must not be able to access another session's conversation history.
- Do not expose OpenAI API keys or raw sensitive upstream error details.

### 5. Empty History Behavior

If the current session has no row in `chat_conversations`, return:

```json
{
  "messages": []
}
```

This must be a successful response, not an error.

### 6. Error Handling

Handle the following cases:

- Missing or invalid current session.
- Database failure while reading `chat_conversations`.
- Missing OpenAI API key.
- Failed OpenAI conversation history request.
- Invalid or unexpected OpenAI response shape.

Expected behavior:

- Return `500` for internal configuration or database errors.
- Return `502` or `503` for OpenAI upstream errors.
- Log upstream errors without exposing sensitive details to the frontend.

## Acceptance Criteria

- A new endpoint returns chat history for the current session.
- The endpoint resolves `conversation_id` from `chat_conversations` using the current `session_id`.
- The endpoint never trusts a client-provided `conversation_id`.
- If no conversation exists for the current session, the endpoint returns `{"messages":[]}`.
- If a conversation exists, the endpoint fetches messages from the OpenAI Conversations API.
- The JSON response contains frontend-usable user and assistant messages.
- Another session cannot access this session's conversation history.
- Upstream OpenAI and database errors are handled with appropriate HTTP status codes.
- Automated tests cover empty history, existing history, session isolation, and upstream failure handling.
