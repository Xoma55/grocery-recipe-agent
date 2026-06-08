<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

final readonly class SessionManager
{
    public function __construct(
        private SessionRepository $repository,
        private SessionConfiguration $configuration,
        private ClockInterface $clock,
    ) {
    }

    public function resolve(?string $sessionId): SessionRecord
    {
        if ($sessionId !== null && $sessionId !== '') {
            $existingSession = $this->repository->find($sessionId);

            if ($existingSession !== null) {
                if (!$existingSession->isExpired($this->clock->now())) {
                    return $existingSession;
                }

                $this->repository->delete($existingSession->id);
            }
        }

        return $this->create();
    }

    private function create(): SessionRecord
    {
        $now = $this->clock->now();
        $session = new SessionRecord(
            bin2hex(random_bytes(32)),
            $now,
            $now->modify(sprintf('+%d seconds', $this->configuration->lifetimeSeconds)),
        );

        $this->repository->save($session);

        return $session;
    }
}
