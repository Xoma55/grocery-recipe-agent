<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

final readonly class SqliteConnectionFactory
{
    public function __construct(
        private SessionConfiguration $configuration,
    ) {
    }

    public function create(): \PDO
    {
        $databasePath = $this->configuration->databasePath;

        if ($databasePath !== ':memory:') {
            $directory = \dirname($databasePath);

            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create SQLite database directory "%s".', $directory));
            }
        }

        $pdo = new \PDO('sqlite:' . $databasePath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        return $pdo;
    }
}
