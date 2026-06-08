<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenAi;

final class OpenAiUpstreamException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 502,
        public readonly ?int $upstreamStatusCode = null,
        public readonly ?string $upstreamResponseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
