<?php

declare(strict_types=1);

namespace App\Infrastructure\Chat;

use App\Infrastructure\OpenAi\OpenAiClientInterface;
use App\Infrastructure\Session\ClockInterface;

final readonly class ChatConversationResolver
{
    public function __construct(
        private ChatConversationRepository $repository,
        private OpenAiClientInterface $openAiClient,
        private ClockInterface $clock,
    ) {
    }

    public function resolveConversationId(string $sessionId): string
    {
        $existing = $this->repository->findBySessionId($sessionId);

        if ($existing !== null) {
            return $existing->conversationId;
        }

        $conversationId = $this->openAiClient->createConversation();
        $this->repository->save($sessionId, $conversationId, $this->clock->now());

        return $conversationId;
    }
}
