<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ApiExceptionRenderer
{
    public function render(Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return ApiResponse::error(
                $exception->getMessage(),
                $exception->status,
                $exception->errors(),
            );
        }

        if ($exception instanceof AuthenticationException) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        if ($exception instanceof AuthorizationException) {
            return ApiResponse::error('Forbidden.', 403);
        }

        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return ApiResponse::error('Resource not found.', 404);
        }

        if ($exception instanceof ThrottleRequestsException) {
            return ApiResponse::error('Too many requests.', 429);
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = $this->resolveHttpExceptionMessage($exception, $status);

            return ApiResponse::error($message, $status);
        }

        return ApiResponse::error($this->genericServerErrorMessage(), 500);
    }

    private function resolveHttpExceptionMessage(HttpExceptionInterface $exception, int $status): string
    {
        if ($status >= 500 && ! config('app.debug')) {
            return $this->genericServerErrorMessage();
        }

        return $exception->getMessage() !== ''
            ? $exception->getMessage()
            : 'Request failed.';
    }

    private function genericServerErrorMessage(): string
    {
        return 'An unexpected error occurred.';
    }
}
