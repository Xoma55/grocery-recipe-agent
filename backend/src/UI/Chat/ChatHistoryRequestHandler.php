<?php

declare(strict_types=1);

namespace App\UI\Chat;

use App\Infrastructure\Chat\ChatConversationRepository;
use App\Infrastructure\OpenAi\OpenAiClientInterface;
use App\Infrastructure\OpenAi\OpenAiUpstreamException;
use App\Infrastructure\Session\SessionRecord;
use App\UI\EventSubscriber\DatabaseSessionSubscriber;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final readonly class ChatHistoryRequestHandler
{
    public function __construct(
        private ChatConversationRepository $conversationRepository,
        private OpenAiClientInterface $openAiClient,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $session = $request->attributes->get(DatabaseSessionSubscriber::REQUEST_ATTRIBUTE);

        if (!$session instanceof SessionRecord) {
            $this->logger->error('Chat history request is missing a resolved application session.');

            return new JsonResponse(['error' => 'Internal server error'], 500);
        }

        try {
            $conversation = $this->conversationRepository->findBySessionId($session->id);

            if ($conversation === null) {
                return new JsonResponse(['messages' => []]);
            }

            $messages = $this->openAiClient->listConversationMessages($conversation->conversationId);
        } catch (OpenAiUpstreamException $exception) {
            $this->logger->error('OpenAI upstream failure while loading chat history.', [
                'exception' => $exception::class,
                'status_code' => $exception->statusCode,
                'upstream_status_code' => $exception->upstreamStatusCode,
                'upstream_response_body' => $exception->upstreamResponseBody,
            ]);

            return new JsonResponse(['error' => 'OpenAI upstream error'], $exception->statusCode);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to load chat history.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return new JsonResponse(['error' => 'Internal server error'], 500);
        }

        return new JsonResponse([
            'messages' => array_map(
                static fn ($message): array => $message->toArray(),
                $messages,
            ),
        ]);
    }
}
