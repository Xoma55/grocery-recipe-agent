import { z } from 'zod';
import { apiRequest } from './client';

export type ChatRole = 'user' | 'assistant';

export type ChatMessage = {
  role: ChatRole;
  content: string;
};

const chatMessageSchema = z.object({
  role: z.enum(['user', 'assistant']),
  content: z.string(),
});

const chatHistorySchema = z.object({
  messages: z.array(chatMessageSchema),
});

const apiErrorSchema = z.object({
  error: z.string(),
});

export type StreamEvent =
  | { type: 'delta'; delta: string }
  | { type: 'done' }
  | { type: 'error'; error: string };

export async function getChatHistory(): Promise<ChatMessage[]> {
  const response = await apiRequest({
    path: '/chat/history',
    method: 'GET',
  });

  if (!response.ok) {
    throw new Error(await readApiError(response, 'Impossible de charger votre conversation.'));
  }

  const payload: unknown = await response.json();
  return chatHistorySchema.parse(payload).messages.toReversed();
}

export async function resetChat(): Promise<void> {
  const response = await apiRequest({
    path: '/chat/reset',
    method: 'POST',
  });

  if (!response.ok) {
    throw new Error(await readApiError(response, 'Impossible de démarrer un nouveau chat.'));
  }
}

export async function sendChatMessage(
  message: string,
  onEvent: (event: StreamEvent) => void,
): Promise<void> {
  const response = await apiRequest({
    path: '/chat',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ message }),
  });

  if (!response.ok) {
    throw new Error(await readApiError(response, 'Impossible d’envoyer votre message.'));
  }

  if (!response.body) {
    throw new Error('La réponse du serveur est vide.');
  }

  await readEventStream(response.body, onEvent);
}

async function readApiError(response: Response, fallback: string): Promise<string> {
  try {
    const payload: unknown = await response.json();
    const parsed = apiErrorSchema.safeParse(payload);
    return parsed.success ? parsed.data.error : fallback;
  } catch {
    return fallback;
  }
}

async function readEventStream(
  stream: ReadableStream<Uint8Array>,
  onEvent: (event: StreamEvent) => void,
): Promise<void> {
  const reader = stream.getReader();
  const decoder = new TextDecoder();
  let buffer = '';

  while (true) {
    const { value, done } = await reader.read();
    buffer += decoder.decode(value, { stream: !done });
    buffer = buffer.replace(/\r\n/g, '\n');

    let boundaryIndex = buffer.indexOf('\n\n');
    while (boundaryIndex !== -1) {
      const rawEvent = buffer.slice(0, boundaryIndex);
      buffer = buffer.slice(boundaryIndex + 2);
      emitParsedEvent(rawEvent, onEvent);
      boundaryIndex = buffer.indexOf('\n\n');
    }

    if (done) {
      break;
    }
  }

  const tail = buffer.trim();
  if (tail) {
    emitParsedEvent(tail, onEvent);
  }
}

function emitParsedEvent(rawEvent: string, onEvent: (event: StreamEvent) => void): void {
  const lines = rawEvent.split('\n');
  const eventName = lines
    .find((line) => line.startsWith('event:'))
    ?.slice('event:'.length)
    .trim();
  const data = lines
    .filter((line) => line.startsWith('data:'))
    .map((line) => line.slice('data:'.length).trimStart())
    .join('\n');

  if (!eventName || !data) {
    return;
  }

  const payload: unknown = JSON.parse(data);

  if (eventName === 'delta') {
    const parsed = z.object({ delta: z.string() }).parse(payload);
    onEvent({ type: 'delta', delta: parsed.delta });
    return;
  }

  if (eventName === 'done') {
    onEvent({ type: 'done' });
    return;
  }

  if (eventName === 'error') {
    const parsed = apiErrorSchema.parse(payload);
    onEvent({ type: 'error', error: parsed.error });
  }
}
