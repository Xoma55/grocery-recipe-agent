CREATE TABLE IF NOT EXISTS sessions (
    id TEXT PRIMARY KEY,
    created_at TEXT NOT NULL,
    expired_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_sessions_expired_at ON sessions (expired_at);
