<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CustomerExceptionHandle
{
    /**
     * Report errors to log or any other storage
     *
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
    }

    /**
     * Rendering exceptions
     *
     * @param Throwable $exception
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function render(Throwable $exception, Request $request)
    {
        $statusCode = method_exists($exception, 'getStatusCode')
            ? $exception->getStatusCode()
            : 500;

        if ($request->is('api/*')) {
            return $this->renderApiException($exception, $statusCode);
        }

        return response()->view('errors.custom', ['exception' => $exception], $statusCode);
    }

    private function renderApiException(Throwable $exception, int $statusCode): JsonResponse
    {
        $message = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode()
        ];

        if (method_exists($exception, 'errors')) {
            $message['errors'] = $exception->errors();
        }

        return response()->json($message, $statusCode);
    }
}
