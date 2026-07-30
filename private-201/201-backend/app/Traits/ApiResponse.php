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
        // Build a more specific error message listing the fields with errors
        $errorArray = is_array($errors) ? $errors : $errors->toArray();
        $errorFields = array_keys($errorArray);
        $fieldNames = [];

        // Map field names to user-friendly names
        $fieldMap = [
            'birthdate' => 'Birthdate',
            'employee_no' => 'Employee Number',
            'email' => 'Email',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'photo' => 'Photo',
            'age' => 'Age',
            'salary' => 'Salary',
        ];

        foreach ($errorFields as $field) {
            $fieldNames[] = $fieldMap[$field] ?? ucfirst(str_replace('_', ' ', $field));
        }

        // Create specific message
        if (count($fieldNames) === 1) {
            $specificMessage = 'Validation error in ' . $fieldNames[0] . ' field.';
        } elseif (count($fieldNames) > 1) {
            $specificMessage = 'Validation errors in the following fields: ' . implode(', ', $fieldNames) . '.';
        } else {
            $specificMessage = $message;
        }

        return response()->json([
            'success' => false,
            'message' => $specificMessage,
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
}
