<?php

declare(strict_types=1);

namespace App\UI\Controller;

use App\UI\Chat\ChatRequestHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ChatController
{
    public function __construct(
        private ChatRequestHandler $chatRequestHandler,
    ) {
    }

    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse|StreamedResponse
    {
        return $this->chatRequestHandler->handle($request);
    }
}
