<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Infrastructure\Session\ClockInterface;
use DateTimeImmutable;

final class MutableClock implements ClockInterface
{
    public function __construct(
        private DateTimeImmutable $now,
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(int $seconds): void
    {
        $this->now = $this->now->modify(sprintf('+%d seconds', $seconds));
    }
}
