<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

final readonly class SessionRecord
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $expiredAt,
    ) {
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->expiredAt <= $now;
    }
}
