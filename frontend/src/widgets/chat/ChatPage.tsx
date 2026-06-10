'use client';

import { FormEvent, useEffect, useMemo, useRef, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import {
  getChatHistory,
  resetChat,
  sendChatMessage,
  type ChatMessage,
  type ChatRole,
  type StreamEvent,
} from '@/shared/api';

type UiMessage = ChatMessage & {
  id: string;
  pending?: boolean;
};

const historyQueryKey = ['chat-history'];
const TYPEWRITER_INTERVAL_MS = 24;
const TYPEWRITER_CHARS_PER_TICK = 4;

export function ChatPage() {
  const queryClient = useQueryClient();
  const [messages, setMessages] = useState<UiMessage[]>([]);
  const [draft, setDraft] = useState('');
  const [streamError, setStreamError] = useState<string | null>(null);
  const [isSending, setIsSending] = useState(false);
  const [isResetting, setIsResetting] = useState(false);
  const [hasHydrated, setHasHydrated] = useState(false);
  const scrollRef = useRef<HTMLDivElement | null>(null);
  const inputRef = useRef<HTMLTextAreaElement | null>(null);
  const shouldStickToBottomRef = useRef(true);
  const streamBufferRef = useRef('');
  const streamTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const streamDrainResolversRef = useRef<Array<() => void>>([]);

  useEffect(() => {
    setHasHydrated(true);
  }, []);

  useEffect(() => () => clearTypewriter(), []);

  const historyQuery = useQuery({
    queryKey: historyQueryKey,
    queryFn: getChatHistory,
    enabled: hasHydrated,
  });

  const isHistoryLoading = hasHydrated && historyQuery.isLoading;

  useEffect(() => {
    if (historyQuery.data) {
      setMessages(historyQuery.data.map(toUiMessage));
    }
  }, [historyQuery.data]);

  useEffect(() => {
    if (shouldStickToBottomRef.current) {
      scrollToBottom();
    }
  }, [messages]);

  const canSend = useMemo(
    () => draft.trim().length > 0 && !isHistoryLoading && !isSending && !isResetting,
    [draft, isHistoryLoading, isResetting, isSending],
  );

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const text = draft.trim();

    if (!text || isSending || isResetting) {
      return;
    }

    const assistantId = createMessageId('assistant');
    setDraft('');
    setStreamError(null);
    setIsSending(true);
    shouldStickToBottomRef.current = true;
    setMessages((current) => [
      ...current,
      { id: createMessageId('user'), role: 'user', content: text },
      { id: assistantId, role: 'assistant', content: '', pending: true },
    ]);

    try {
      await sendChatMessage(text, (streamEvent) => handleStreamEvent(streamEvent, assistantId));
      await flushQueuedAssistantText(assistantId);
      setMessages((current) =>
        current.map((message) =>
          message.id === assistantId ? { ...message, pending: false } : message,
        ),
      );
      await queryClient.invalidateQueries({ queryKey: historyQueryKey });
    } catch (error) {
      await flushQueuedAssistantText(assistantId);
      setStreamError(getErrorMessage(error, 'Le message n’a pas pu être envoyé.'));
      setMessages((current) =>
        current.map((message) =>
          message.id === assistantId
            ? {
                ...message,
                content: message.content || 'La réponse a été interrompue.',
                pending: false,
              }
            : message,
        ),
      );
    } finally {
      setIsSending(false);
      inputRef.current?.focus();
    }
  }

  async function handleReset() {
    if (isSending || isResetting) {
      return;
    }

    setStreamError(null);
    setIsResetting(true);

    try {
      await resetChat();
      clearTypewriter();
      setMessages([]);
      queryClient.setQueryData(historyQueryKey, []);
      shouldStickToBottomRef.current = true;
    } catch (error) {
      setStreamError(getErrorMessage(error, 'Le nouveau chat n’a pas pu être démarré.'));
    } finally {
      setIsResetting(false);
      inputRef.current?.focus();
    }
  }

  function handleStreamEvent(streamEvent: StreamEvent, assistantId: string) {
    if (streamEvent.type === 'delta') {
      queueAssistantText(assistantId, streamEvent.delta);
      return;
    }

    if (streamEvent.type === 'done') {
      return;
    }

    setStreamError(streamEvent.error);
    setMessages((current) =>
      current.map((message) =>
        message.id === assistantId ? { ...message, pending: false } : message,
      ),
    );
  }

  function queueAssistantText(assistantId: string, delta: string) {
    streamBufferRef.current += delta;
    startTypewriter(assistantId);
  }

  function startTypewriter(assistantId: string) {
    if (streamTimerRef.current !== null) {
      return;
    }

    streamTimerRef.current = setInterval(() => {
      const nextChunk = streamBufferRef.current.slice(0, TYPEWRITER_CHARS_PER_TICK);
      streamBufferRef.current = streamBufferRef.current.slice(TYPEWRITER_CHARS_PER_TICK);

      if (nextChunk) {
        setMessages((current) =>
          current.map((message) =>
            message.id === assistantId
              ? { ...message, content: `${message.content}${nextChunk}` }
              : message,
          ),
        );
      }

      if (!streamBufferRef.current) {
        clearTypewriter();
      }
    }, TYPEWRITER_INTERVAL_MS);
  }

  function clearTypewriter() {
    if (streamTimerRef.current !== null) {
      clearInterval(streamTimerRef.current);
      streamTimerRef.current = null;
    }

    const resolvers = streamDrainResolversRef.current;
    streamDrainResolversRef.current = [];
    resolvers.forEach((resolve) => resolve());
  }

  function flushQueuedAssistantText(assistantId: string): Promise<void> {
    if (!streamBufferRef.current) {
      clearTypewriter();
      return Promise.resolve();
    }

    startTypewriter(assistantId);

    return new Promise((resolve) => {
      streamDrainResolversRef.current.push(resolve);
    });
  }

  function handleScroll() {
    const node = scrollRef.current;
    if (!node) {
      return;
    }

    const distanceFromBottom = node.scrollHeight - node.scrollTop - node.clientHeight;
    shouldStickToBottomRef.current = distanceFromBottom < 96;
  }

  function scrollToBottom() {
    const node = scrollRef.current;
    if (!node) {
      return;
    }

    requestAnimationFrame(() => {
      node.scrollTop = node.scrollHeight;
    });
  }

  const showEmptyState = hasHydrated && !isHistoryLoading && messages.length === 0;

  return (
    <main className="chat-shell">
      <section className="chat-panel" aria-label="Assistant recettes en magasin">
        <header className="chat-topbar">
          <div>
            <p className="eyebrow">Assistant recettes</p>
            <h1>Votre idée repas pour ce soir</h1>
          </div>
          <button
            className="secondary-button"
            type="button"
            onClick={handleReset}
            disabled={isSending || isResetting}
            aria-label="Démarrer un nouveau chat"
          >
            {isResetting ? 'Nouveau...' : 'New chat'}
          </button>
        </header>

        <div className="message-list" ref={scrollRef} onScroll={handleScroll} aria-live="polite">
          {isHistoryLoading ? <StatusCard text="Chargement de votre conversation..." /> : null}
          {historyQuery.isError ? (
            <StatusCard text={getErrorMessage(historyQuery.error, 'Impossible de charger votre conversation.')} tone="error" />
          ) : null}
          {showEmptyState ? (
            <div className="empty-state">
              <p className="eyebrow">Prêt en rayon</p>
              <h2>Décrivez deux ingrédients, une envie ou une contrainte.</h2>
              <p>Exemple : “J’ai des tomates et des oeufs, je veux un dîner végétarien rapide.”</p>
            </div>
          ) : null}
          {messages.map((message) => (
            <MessageBubble key={message.id} message={message} />
          ))}
          {streamError ? <StatusCard text={streamError} tone="error" /> : null}
        </div>

        <form className="composer" onSubmit={handleSubmit}>
          <label className="sr-only" htmlFor="chat-message">
            Message pour l’assistant recettes
          </label>
          <textarea
            id="chat-message"
            ref={inputRef}
            value={draft}
            onChange={(event) => setDraft(event.target.value)}
            placeholder="Ex. Je veux cuisiner avec du poulet et des courgettes..."
            rows={2}
            disabled={isHistoryLoading || isSending || isResetting}
            onKeyDown={(event) => {
              if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                event.currentTarget.form?.requestSubmit();
              }
            }}
          />
          <button className="primary-button" type="submit" disabled={!canSend} aria-label="Envoyer le message">
            {isSending ? 'Envoi...' : 'Envoyer'}
          </button>
        </form>
      </section>
    </main>
  );
}

function MessageBubble({ message }: { message: UiMessage }) {
  const label = message.role === 'user' ? 'Vous' : 'Assistant';

  return (
    <article className={`message-row ${message.role === 'user' ? 'is-user' : 'is-assistant'}`}>
      <div className="message-bubble">
        <span>{label}</span>
        <p>{message.content || (message.pending ? '...' : '')}</p>
      </div>
    </article>
  );
}

function StatusCard({ text, tone = 'neutral' }: { text: string; tone?: 'neutral' | 'error' }) {
  return <p className={`status-card ${tone === 'error' ? 'is-error' : ''}`}>{text}</p>;
}

function toUiMessage(message: ChatMessage, index: number): UiMessage {
  return {
    ...message,
    id: `history-${index}-${message.role}`,
  };
}

function createMessageId(role: ChatRole): string {
  return `${role}-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function getErrorMessage(error: unknown, fallback: string): string {
  return error instanceof Error && error.message ? error.message : fallback;
}
