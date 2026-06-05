<?php

declare(strict_types=1);

namespace App\UI\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController
{
    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Chat endpoint is ready',
        ]);
    }
}
