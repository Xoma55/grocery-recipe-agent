<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Infrastructure\OpenAi\OpenAiClientInterface;
use App\Infrastructure\OpenAi\OpenAiConversationMessage;
use App\Infrastructure\OpenAi\OpenAiResponseStream;
use App\Infrastructure\OpenAi\OpenAiUpstreamException;
use RuntimeException;

final class FakeOpenAiClient implements OpenAiClientInterface
{
    /**
     * @var list<string>
     */
    public array $listedConversationIds = [];

    /**
     * @param array<string, list<OpenAiConversationMessage>> $messagesByConversationId
     */
    public function __construct(
        private readonly array $messagesByConversationId = [],
        private readonly ?OpenAiUpstreamException $historyException = null,
    ) {
    }

    public function createConversation(): string
    {
        return 'conv_created_by_fake';
    }

    public function createStreamingResponse(string $conversationId, string $instructions, string $message): OpenAiResponseStream
    {
        $stream = fopen('php://temp', 'rb+');

        if ($stream === false) {
            throw new RuntimeException('Unable to create fake stream.');
        }

        return new OpenAiResponseStream($stream);
    }

    public function listConversationMessages(string $conversationId): array
    {
        $this->listedConversationIds[] = $conversationId;

        if ($this->historyException !== null) {
            throw $this->historyException;
        }

        return $this->messagesByConversationId[$conversationId] ?? [];
    }
}
