<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenAi;

final readonly class OpenAiConfiguration
{
    public function __construct(
        public string $apiKey,
        public string $model,
        public string $baseUrl,
    ) {
    }
}
