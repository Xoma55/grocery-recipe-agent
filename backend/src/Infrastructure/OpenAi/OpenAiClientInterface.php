<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenAi;

interface OpenAiClientInterface
{
    public function createConversation(): string;

    public function createStreamingResponse(string $conversationId, string $instructions, string $message): OpenAiResponseStream;
}
