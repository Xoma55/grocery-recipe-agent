<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
