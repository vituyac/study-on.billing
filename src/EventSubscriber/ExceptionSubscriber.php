<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onJsonException',
        ];
    }

    public function onJsonException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        if (!$event->isMainRequest() || $e instanceof UnprocessableEntityHttpException) {
            return;
        }

        $code = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        $event->setResponse(new JsonResponse([
            'code' => $code,
            'message' => $code >= 500
                ? 'Internal Server Error'
                : ($e->getMessage() ?: Response::$statusTexts[$code] ?? 'Error'),
        ], $code));
    }
}
