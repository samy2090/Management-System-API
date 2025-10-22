<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            $result = $this->authService->login($request->only(['email', 'password']));

            return $this->sendResponse([
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => 'Bearer'
            ], 'Login successful');
            
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Login validation failed');
        } catch (\Exception $e) {
            return $this->handleException($e, 'login');
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return $this->sendResponse(null, 'Logged out successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'logout');
        }
    }
}