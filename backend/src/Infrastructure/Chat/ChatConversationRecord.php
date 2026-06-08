<?php

declare(strict_types=1);

namespace App\Infrastructure\Chat;

final readonly class ChatConversationRecord
{
    public function __construct(
        public int $id,
        public string $sessionId,
        public string $conversationId,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
