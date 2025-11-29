<?php

namespace App\EventSubscriber;

use App\Exception\User\UserNotAuthenticatedException;
use App\Repository\RefreshTokenRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RefreshTokenSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly RefreshTokenRepository $refreshRepo) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 8]]; // before controller
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) { return; }
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Apply only to refresh & logout endpoints
        if (!in_array($path, ['/api/auth/refresh', '/api/auth/logout'], true)) {
            return;
        }

        $token = $request->cookies->get('refresh_token');
        if (!$token || strlen($token) !== 64) {
            throw new UserNotAuthenticatedException();
        }
        $entity = $this->refreshRepo->findOneByRefreshToken($token);
        if (!$entity || $entity->getValid() < new \DateTimeImmutable()) {
            throw new UserNotAuthenticatedException();
        }
    }
}

