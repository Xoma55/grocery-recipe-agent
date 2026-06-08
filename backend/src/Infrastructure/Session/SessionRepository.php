<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

final class SessionRepository
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
            throw new \RuntimeException(sprintf('Unable to read session migration "%s".', $this->migrationPath));
        }

        $this->connection()->exec($sql);
    }

    public function find(string $id): ?SessionRecord
    {
        $this->initializeSchema();

        $statement = $this->connection()->prepare('SELECT id, created_at, expired_at FROM sessions WHERE id = :id');
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return new SessionRecord(
            (string) $row['id'],
            new \DateTimeImmutable((string) $row['created_at']),
            new \DateTimeImmutable((string) $row['expired_at']),
        );
    }

    public function save(SessionRecord $session): void
    {
        $this->initializeSchema();

        $statement = $this->connection()->prepare(
            'INSERT INTO sessions (id, created_at, expired_at) VALUES (:id, :created_at, :expired_at)'
        );

        $statement->execute([
            'id' => $session->id,
            'created_at' => $session->createdAt->format(\DateTimeInterface::ATOM),
            'expired_at' => $session->expiredAt->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function delete(string $id): void
    {
        $this->initializeSchema();

        $statement = $this->connection()->prepare('DELETE FROM sessions WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    /**
     * @return list<SessionRecord>
     */
    public function all(): array
    {
        $this->initializeSchema();

        $rows = $this->connection()->query('SELECT id, created_at, expired_at FROM sessions ORDER BY created_at')->fetchAll();

        return array_map(
            static fn (array $row): SessionRecord => new SessionRecord(
                (string) $row['id'],
                new \DateTimeImmutable((string) $row['created_at']),
                new \DateTimeImmutable((string) $row['expired_at']),
            ),
            $rows,
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
