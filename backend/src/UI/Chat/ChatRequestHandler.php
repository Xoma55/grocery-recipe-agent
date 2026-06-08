<?php

declare(strict_types=1);

namespace App\UI\Chat;

use App\Infrastructure\Chat\ChatConversationResolver;
use App\Infrastructure\OpenAi\OpenAiClientInterface;
use App\Infrastructure\OpenAi\OpenAiUpstreamException;
use App\Infrastructure\Session\SessionRecord;
use App\Infrastructure\SystemPrompt\SystemPromptAssemblyService;
use App\UI\EventSubscriber\DatabaseSessionSubscriber;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ChatRequestHandler
{
    public function __construct(
        private ChatConversationResolver $conversationResolver,
        private SystemPromptAssemblyService $systemPromptAssemblyService,
        private OpenAiClientInterface $openAiClient,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Request $request): JsonResponse|StreamedResponse
    {
        $message = $this->messageFromRequest($request);

        if ($message === null) {
            return new JsonResponse(['error' => 'message must be a non-empty string'], 400);
        }

        $session = $request->attributes->get(DatabaseSessionSubscriber::REQUEST_ATTRIBUTE);

        if (!$session instanceof SessionRecord) {
            $this->logger->error('Chat request is missing a resolved application session.');

            return new JsonResponse(['error' => 'Internal server error'], 500);
        }

        try {
            $conversationId = $this->conversationResolver->resolveConversationId($session->id);
            $instructions = $this->systemPromptAssemblyService->getAssembledSystemPrompt();
            $stream = $this->openAiClient->createStreamingResponse($conversationId, $instructions, $message);
        } catch (OpenAiUpstreamException $exception) {
            $this->logger->error('OpenAI upstream failure before streaming chat response.', [
                'exception' => $exception::class,
                'status_code' => $exception->statusCode,
                'upstream_status_code' => $exception->upstreamStatusCode,
                'upstream_response_body' => $exception->upstreamResponseBody,
            ]);

            return new JsonResponse(['error' => 'OpenAI upstream error'], $exception->statusCode);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to prepare chat response.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return new JsonResponse(['error' => 'Internal server error'], 500);
        }

        return new StreamedResponse(function () use ($stream): void {
            $send = static function (string $event, array $data): void {
                echo 'event: ' . $event . "\n";
                echo 'data: ' . json_encode($data, JSON_THROW_ON_ERROR) . "\n\n";
                flush();
            };

            try {
                $stream->forwardTextDeltas(static fn (string $delta): null => $send('delta', ['delta' => $delta]));
                $send('done', ['done' => true]);
            } catch (\Throwable) {
                $send('error', ['error' => 'OpenAI stream interrupted']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function messageFromRequest(Request $request): ?string
    {
        $payload = json_decode($request->getContent(), true);
        $message = is_array($payload) ? ($payload['message'] ?? null) : $request->request->get('message');

        if (!is_string($message)) {
            return null;
        }

        $message = trim($message);

        return $message === '' ? null : $message;
    }
}
