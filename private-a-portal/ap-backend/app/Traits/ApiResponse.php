<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Build a standardized success JSON response.
     */
    protected function successResponse($data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Build a standardized error JSON response.
     */
    protected function errorResponse(string $message = 'Error', int $status = 400, $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Build a standardized server error JSON response.
     */
    protected function serverErrorResponse(string $message = 'Internal Server Error', int $status = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Server Error'
        ], $status);
    }

    /**
     * Build a standardized validation error JSON response.
     */
    protected function validationErrorResponse($errors, string $message = 'Validation failed', int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}


