CREATE TABLE IF NOT EXISTS chat_conversations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    conversation_id TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS uniq_chat_conversations_session_id ON chat_conversations (session_id);
CREATE INDEX IF NOT EXISTS idx_chat_conversations_conversation_id ON chat_conversations (conversation_id);
