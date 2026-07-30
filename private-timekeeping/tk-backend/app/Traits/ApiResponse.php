<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success response
     */
    protected function successResponse($data = null, $message = 'Success', $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Error response
     */
    protected function errorResponse($message = 'Error', $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse($errors, $message = 'Validation failed'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse($message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 404);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorizedResponse($message = 'Unauthorized'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 401);
    }

    /**
     * Forbidden response
     */
    protected function forbiddenResponse($message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 403);
    }

    /**
     * Server error response
     */
    protected function serverErrorResponse($message = 'Internal server error'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 500);
    }

    /**
     * Warning response
     */
    protected function warningResponse($message = 'Warning', $type = 'warning'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'type' => $type
        ], 200);
    }
} 