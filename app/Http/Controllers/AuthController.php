<?php
namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request): JsonResponse {
        $result = $this->authService->login($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully',
            'data' => $result
        ]);
    }
}
