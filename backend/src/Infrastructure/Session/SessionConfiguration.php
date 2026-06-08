<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

final readonly class SessionConfiguration
{
    public string $databasePath;

    public function __construct(
        public int $lifetimeSeconds,
        string $databaseUrl,
    ) {
        if ($this->lifetimeSeconds <= 0) {
            throw new \InvalidArgumentException('SESSION_LIFETIME must be a positive integer.');
        }

        if ($databaseUrl === '') {
            throw new \InvalidArgumentException('DATABASE_URL must not be empty.');
        }

        $this->databasePath = $this->resolveDatabasePath($databaseUrl);
    }

    private function resolveDatabasePath(string $databaseUrl): string
    {
        if ($databaseUrl === 'sqlite:///:memory:') {
            return ':memory:';
        }

        if (!str_starts_with($databaseUrl, 'sqlite:///')) {
            throw new \InvalidArgumentException('DATABASE_URL must use the sqlite:/// scheme.');
        }

        $databasePath = substr($databaseUrl, strlen('sqlite:///'));

        if ($databasePath === '') {
            throw new \InvalidArgumentException('DATABASE_URL must contain a SQLite database path.');
        }

        return '/' . ltrim($databasePath, '/');
    }
}
