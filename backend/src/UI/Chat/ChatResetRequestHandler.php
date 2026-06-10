<?php

declare(strict_types=1);

namespace App\UI\Chat;

use App\Infrastructure\Chat\ChatConversationRepository;
use App\Infrastructure\Session\SessionRecord;
use App\UI\EventSubscriber\DatabaseSessionSubscriber;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ChatResetRequestHandler
{
    public function __construct(
        private ChatConversationRepository $conversationRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Request $request): Response
    {
        $session = $request->attributes->get(DatabaseSessionSubscriber::REQUEST_ATTRIBUTE);

        if (!$session instanceof SessionRecord) {
            $this->logger->error('Chat reset request is missing a resolved application session.');

            return new Response('{"error":"Internal server error"}', 500, [
                'Content-Type' => 'application/json',
            ]);
        }

        try {
            $this->conversationRepository->deleteBySessionId($session->id);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to reset chat conversation.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return new Response('{"error":"Internal server error"}', 500, [
                'Content-Type' => 'application/json',
            ]);
        }

        return new Response(null, 204);
    }
}
