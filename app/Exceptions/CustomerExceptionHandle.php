<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class CustomerExceptionHandle
{
    /**
     * Report errors to log or any other storage.
     *
     * Reports exceptions to the log file for debugging and monitoring purposes.
     * Critical exceptions are logged with higher priority.
     */
    public function report(Throwable $exception): void
    {
        if ($this->shouldReport($exception)) {
            Log::error($exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }

    /**
     * Render exceptions for HTTP responses.
     *
     * Determines whether to render API-specific JSON responses or web view responses
     * based on the request type.
     */
    public function render(Throwable $exception, Request $request): JsonResponse|Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiException($exception, $request);
        }

        $statusCode = $this->getStatusCode($exception);

        return response()->view('errors.index', ['exception' => $exception], $statusCode);
    }

    /**
     * Render API exception as JSON response.
     *
     * Handles different types of exceptions and returns appropriate JSON responses
     * with proper status codes and error messages.
     */
    private function renderApiException(Throwable $exception, Request $request): JsonResponse
    {
        $statusCode = $this->getStatusCode($exception);
        $response = [
            'success' => false,
            'message' => $this->getExceptionMessage($exception),
        ];

        // Handle validation exceptions
        if ($exception instanceof ValidationException) {
            $response['errors'] = $exception->errors();
            $response['message'] = 'The given data was invalid.';
        }

        // Handle model not found exceptions
        if ($exception instanceof ModelNotFoundException) {
            $response['message'] = 'Resource not found.';
            $model = $exception->getModel();
            if ($model) {
                $response['resource'] = class_basename($model);
            }
        }

        // Handle authentication exceptions
        if ($exception instanceof AuthenticationException) {
            $response['message'] = 'Unauthenticated.';
        }

        // Handle authorization exceptions
        if ($exception instanceof AuthorizationException) {
            $response['message'] = 'This action is unauthorized.';
        }

        // Handle HTTP exceptions
        if ($exception instanceof NotFoundHttpException) {
            $response['message'] = 'The requested resource was not found.';
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            $response['message'] = 'The specified method for the request is invalid.';
        }

        if ($exception instanceof TooManyRequestsHttpException) {
            $response['message'] = 'Too many requests. Please slow down.';
        }

        // Handle database exceptions
        if ($exception instanceof QueryException) {
            $response['message'] = 'Database error occurred.';
            if (config('app.debug')) {
                $response['sql_error'] = $exception->getMessage();
            }
        }

        // Add debug information if in debug mode
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Get the status code from the exception.
     *
     * Determines the appropriate HTTP status code based on the exception type.
     */
    private function getStatusCode(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof ValidationException => Response::HTTP_UNPROCESSABLE_ENTITY,
            $exception instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
            $exception instanceof AuthorizationException => Response::HTTP_FORBIDDEN,
            $exception instanceof ModelNotFoundException => Response::HTTP_NOT_FOUND,
            $exception instanceof NotFoundHttpException => Response::HTTP_NOT_FOUND,
            $exception instanceof MethodNotAllowedHttpException => Response::HTTP_METHOD_NOT_ALLOWED,
            $exception instanceof TooManyRequestsHttpException => Response::HTTP_TOO_MANY_REQUESTS,
            $exception instanceof HttpException => $exception->getStatusCode(),
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }

    /**
     * Get a user-friendly message from the exception.
     *
     * Returns the exception message or a generic error message based on the exception type.
     */
    private function getExceptionMessage(Throwable $exception): string
    {
        if ($exception instanceof HttpException && $exception->getMessage()) {
            return $exception->getMessage();
        }

        if ($exception instanceof ValidationException) {
            return 'The given data was invalid.';
        }

        if (! config('app.debug') && $exception->getMessage()) {
            return 'An error occurred while processing your request.';
        }

        return $exception->getMessage() ?: 'An error occurred while processing your request.';
    }

    /**
     * Determine if the exception should be reported.
     *
     * Filters out exceptions that should not be logged.
     */
    private function shouldReport(Throwable $exception): bool
    {
        $dontReport = [
            AuthenticationException::class,
            AuthorizationException::class,
            ValidationException::class,
            NotFoundHttpException::class,
            ModelNotFoundException::class,
        ];

        foreach ($dontReport as $type) {
            if ($exception instanceof $type) {
                return false;
            }
        }

        return true;
    }
}
