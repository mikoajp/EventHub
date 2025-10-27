<?php

namespace App\EventListener;

use App\Exception\ApplicationException;
use App\Exception\DomainException as AppDomainException;
use App\Exception\Validation\ValidationException;
use App\Exception\Validation\InvalidJsonException;
use App\Exception\Authorization\AuthenticationRequiredException;
use App\Exception\Authorization\InsufficientPermissionsException;
use App\Exception\User\UserNotAuthenticatedException;
use App\Exception\User\UserNotFoundException;
use App\Exception\Event\EventNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $payload = [
            'error' => [
                'message' => $e->getMessage(),
                'type' => (new \ReflectionClass($e))->getShortName(),
            ],
        ];

        switch (true) {
            case $e instanceof ValidationException:
                $status = Response::HTTP_BAD_REQUEST;
                $payload = $e->toArray();
                break;
            case $e instanceof InvalidJsonException:
            case $e instanceof BadRequestHttpException:
                $status = Response::HTTP_BAD_REQUEST;
                break;
            case $e instanceof AuthenticationRequiredException:
            case $e instanceof UserNotAuthenticatedException:
                $status = Response::HTTP_UNAUTHORIZED;
                break;
            case $e instanceof InsufficientPermissionsException:
                $status = Response::HTTP_FORBIDDEN;
                break;
            case $e instanceof EventNotFoundException:
            case $e instanceof UserNotFoundException:
            case $e instanceof NotFoundHttpException:
                $status = Response::HTTP_NOT_FOUND;
                break;
            case $e instanceof MethodNotAllowedHttpException:
                $status = Response::HTTP_METHOD_NOT_ALLOWED;
                break;
            case $e instanceof AppDomainException:
                $status = Response::HTTP_UNPROCESSABLE_ENTITY;
                break;
            case $e instanceof ApplicationException:
                $status = Response::HTTP_BAD_REQUEST;
                break;
        }

        $event->setResponse(new JsonResponse($payload, $status));
    }
}
