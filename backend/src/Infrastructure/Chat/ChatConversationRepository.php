<?php

declare(strict_types=1);

namespace App\Infrastructure\Chat;

use App\Infrastructure\Session\SqliteConnectionFactory;

final class ChatConversationRepository
{
    private ?\PDO $connection = null;

    public function __construct(
        private readonly SqliteConnectionFactory $connectionFactory,
        private readonly string $migrationPath,
    ) {
    }

    public function initializeSchema(): void
    {
        $sql = file_get_contents($this->migrationPath);

        if ($sql === false) {
            throw new \RuntimeException(sprintf('Unable to read chat conversation migration "%s".', $this->migrationPath));
        }

        $this->connection()->exec($sql);
    }

    public function findBySessionId(string $sessionId): ?ChatConversationRecord
    {
        $this->initializeSchema();

        $statement = $this->connection()->prepare(
            'SELECT id, session_id, conversation_id, created_at, updated_at FROM chat_conversations WHERE session_id = :session_id'
        );
        $statement->execute(['session_id' => $sessionId]);

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->recordFromRow($row);
    }

    public function save(string $sessionId, string $conversationId, \DateTimeImmutable $now): ChatConversationRecord
    {
        $this->initializeSchema();

        $statement = $this->connection()->prepare(
            'INSERT INTO chat_conversations (session_id, conversation_id, created_at, updated_at)
             VALUES (:session_id, :conversation_id, :created_at, :updated_at)'
        );
        $statement->execute([
            'session_id' => $sessionId,
            'conversation_id' => $conversationId,
            'created_at' => $now->format(\DateTimeInterface::ATOM),
            'updated_at' => $now->format(\DateTimeInterface::ATOM),
        ]);

        $record = $this->findBySessionId($sessionId);

        if ($record === null) {
            throw new \RuntimeException('Saved chat conversation could not be loaded.');
        }

        return $record;
    }

    /**
     * @return list<ChatConversationRecord>
     */
    public function all(): array
    {
        $this->initializeSchema();

        $rows = $this->connection()
            ->query('SELECT id, session_id, conversation_id, created_at, updated_at FROM chat_conversations ORDER BY created_at')
            ->fetchAll();

        return array_map(fn (array $row): ChatConversationRecord => $this->recordFromRow($row), $rows);
    }

    /**
     * @param array{id: int|string, session_id: string, conversation_id: string, created_at: string, updated_at: string} $row
     */
    private function recordFromRow(array $row): ChatConversationRecord
    {
        return new ChatConversationRecord(
            (int) $row['id'],
            (string) $row['session_id'],
            (string) $row['conversation_id'],
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['updated_at']),
        );
    }

    private function connection(): \PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->connectionFactory->create();
        }

        return $this->connection;
    }
}
