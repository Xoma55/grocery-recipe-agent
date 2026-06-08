<?php

declare(strict_types=1);

namespace App\UI\EventSubscriber;

use App\Infrastructure\Session\SessionManager;
use App\Infrastructure\Session\SessionRecord;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class DatabaseSessionSubscriber implements EventSubscriberInterface
{
    public const COOKIE_NAME = 'grocery_session';
    public const REQUEST_ATTRIBUTE = '_grocery_session';

    public function __construct(
        private SessionManager $sessionManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 64],
            KernelEvents::RESPONSE => ['onKernelResponse', -64],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $this->sessionManager->resolve($request->cookies->get(self::COOKIE_NAME));

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $session);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $session = $event->getRequest()->attributes->get(self::REQUEST_ATTRIBUTE);

        if (!$session instanceof SessionRecord) {
            return;
        }

        $event->getResponse()->headers->setCookie(Cookie::create(
            self::COOKIE_NAME,
            $session->id,
            $session->expiredAt,
            '/',
            null,
            null,
            true,
            false,
            Cookie::SAMESITE_LAX,
        ));
    }
}
