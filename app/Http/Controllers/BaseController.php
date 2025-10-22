<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Success response method
     */
    protected function sendResponse($data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ], $code);
    }

    /**
     * Error response method
     */
    protected function sendError(string $message, $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Validation error response
     */
    protected function sendValidationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'error_code' => 'VALIDATION_ERROR',
            'timestamp' => now()->toISOString()
        ], 422);
    }

    /**
     * Not found error response
     */
    protected function sendNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'NOT_FOUND',
            'timestamp' => now()->toISOString()
        ], 404);
    }

    /**
     * Unauthorized error response
     */
    protected function sendUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED',
            'timestamp' => now()->toISOString()
        ], 401);
    }

    /**
     * Forbidden error response
     */
    protected function sendForbidden(string $message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'FORBIDDEN',
            'timestamp' => now()->toISOString()
        ], 403);
    }

    /**
     * Server error response
     */
    protected function sendServerError(string $message = 'Internal server error'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'SERVER_ERROR',
            'timestamp' => now()->toISOString()
        ], 500);
    }

    /**
     * Handle common exceptions in a simple way
     */
    protected function handleException(\Exception $e, string $operation = 'operation')
    {
        // Log the error for debugging
        \Log::error("Error in {$operation}", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => auth()->id()
        ]);

        // Simple error classification
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->sendNotFound();
        }

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->sendValidationError($e->errors());
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->sendUnauthorized();
        }

        if ($e instanceof \Illuminate\Validation\UnauthorizedException) {
            return $this->sendForbidden($e->getMessage());
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->sendForbidden($e->getMessage());
        }

        // For any other error, return server error
        return $this->sendServerError("Failed to perform {$operation}");
    }

    /**
     * Send paginated response
     */
    protected function sendPaginatedResponse($paginatedData, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginatedData->items(),
            'meta' => [
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem(),
            ],
            'timestamp' => now()->toISOString()
        ]);
    }
}