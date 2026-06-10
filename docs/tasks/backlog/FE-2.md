# Build Main Chat Page

Status: Backlog

## Summary

Build the main shopper-facing page as a mobile-first chat interface.

The page must show a scrolling list of message bubbles, a message input pinned to the bottom, a send button, and a top action for starting a new chat. The UI must connect to the existing backend chat endpoints documented in `docs/context/api.md` and preserve the anonymous backend session through browser credentials.

## Goal

A shopper can open the application, see their current anonymous chat history, send a message, receive a streamed assistant reply, and start a fresh chat without signing up or managing any conversation identifier.

## Scope

- Implement the main page as the chat screen.
- Add responsive chat layout for phone and desktop viewports.
- Load existing chat history for the current anonymous session.
- Send user messages to the backend and render streamed assistant responses.
- Add a top "New chat" action that resets the current conversation.
- Use the shared frontend API layer created in `FE-1`.
- Keep conversation ownership server-side; the frontend must not create, store, or send conversation IDs.

## API Integration

Use the backend API documented in `docs/context/api.md`:

- `GET /api/chat/history` loads normalized messages for the current anonymous session.
- `POST /api/chat` sends a user message and streams the assistant response as Server-Sent Events.
- `POST /api/chat/reset` starts a new chat for the current anonymous session and returns `204 No Content`.

All chat API requests must:

- Use the configured backend API base URL from `NEXT_PUBLIC_BACKEND_API_URL` through `frontend/src/shared/api/`.
- Include browser credentials so the `grocery_session` cookie is sent and updated.
- Treat the backend session cookie as `HttpOnly`; frontend code must not read or write it manually.

## Requirements

### 1. Main Page Layout

- The application home page renders the chat interface.
- A top bar contains a clearly visible "New chat" button.
- The message area fills the available viewport height between the top bar and bottom composer.
- Messages render as a vertical scrolling list of bubbles.
- User and assistant messages have visually distinct alignment or styling.
- The input composer stays pinned to the bottom of the viewport.
- The send button is available next to the input on usable viewport widths.
- The layout works on phone-sized screens and desktop screens without horizontal overflow.

### 2. History Loading

- On page load, the frontend requests `GET /api/chat/history`.
- Existing `user` and `assistant` messages are rendered in order.
- An empty history renders a useful empty state or starter prompt without blocking input.
- Loading and error states are visible and do not break the layout.

### 3. Sending Messages

- The user can type a non-empty message and submit it using the send button.
- Empty or whitespace-only messages are not sent.
- The submitted user message appears in the chat immediately or as soon as the request starts.
- While a message is being sent and streamed, duplicate sends are prevented.
- Backend validation errors are shown in a user-readable way.

### 4. Streaming Assistant Response

- `POST /api/chat` is consumed as a `text/event-stream` response.
- `delta` events append text to the active assistant bubble progressively.
- The `done` event marks the assistant response as complete.
- A streamed `error` event or interrupted stream leaves a visible error state and does not remove already displayed user input.
- The message list scrolls to keep the latest content visible while streaming unless the user has intentionally scrolled away.

### 5. New Chat Action

- Clicking "New chat" calls `POST /api/chat/reset`.
- On successful reset, the visible message list is cleared.
- The anonymous application session is preserved; the frontend must not clear cookies or local session data.
- The next sent message starts a new backend conversation through the existing backend behavior.
- Reset failure is shown to the user without clearing the current visible messages.

### 6. Frontend Architecture

- Chat API calls are implemented under `frontend/src/shared/api/` or a Feature-Sliced layer that depends on it.
- UI state uses React state where appropriate.
- Server state may use React Query, consistent with `docs/context/frontend.md`.
- No global client-side state library is added.
- Backend API URLs are not hardcoded in feature or page components.
- Client Components are used only where interactivity requires them.

### 7. Accessibility and Usability

- The message input has an accessible label or equivalent accessible name.
- The send button has a clear accessible name.
- The "New chat" button has a clear accessible name.
- Keyboard submission is supported.
- Focus remains usable after sending a message and after starting a new chat.
- The interface remains readable on small phone screens.

## Acceptance Criteria

- The home page is the chat page.
- A top "New chat" button is visible.
- A scrolling list of message bubbles is rendered.
- User and assistant bubbles are visually distinguishable.
- The message input is pinned to the bottom of the viewport.
- The send button is placed with the input and works on mobile and desktop layouts.
- The layout is responsive and has no horizontal overflow on phone-sized screens.
- On page load, `GET /api/chat/history` is called with credentials included.
- Existing history messages are rendered in backend order.
- Empty history renders a usable empty state and allows sending a message.
- Whitespace-only messages cannot be sent.
- Sending a valid message calls `POST /api/chat` with credentials included and a JSON body containing `message`.
- The frontend does not send any `conversation_id` value.
- The user message is displayed when sending starts.
- `delta` SSE events from `POST /api/chat` progressively update the assistant bubble.
- A `done` SSE event completes the active assistant response.
- Stream errors or backend errors are visible to the user without deleting existing messages.
- Duplicate sends are prevented while a response is in progress.
- The chat scroll position follows new messages and streamed content when the user is at the bottom.
- Clicking "New chat" calls `POST /api/chat/reset` with credentials included.
- Successful reset clears the visible message list.
- Failed reset keeps the current visible message list and shows an error.
- Frontend code does not read, write, clear, or manually construct the `grocery_session` cookie.
- Backend API access goes through the shared API configuration and uses `NEXT_PUBLIC_BACKEND_API_URL`.
- No backend API URL is hardcoded in page or feature components.
- No global client-side state library is added.
- The chat input, send button, and new chat button are accessible by keyboard and have accessible names.
- The implementation passes frontend lint/type checks and any relevant automated tests available in the project.
