<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenAi;

final readonly class OpenAiConversationMessage
{
    public function __construct(
        public string $role,
        public string $content,
    ) {
    }

    /**
     * @return array{role: string, content: string}
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }
}
