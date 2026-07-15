<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function __construct(protected AuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated(),
            $request->input('device_name', 'mobile')
        );

        return $this->respond([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Logged in successfully');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        if ($token = $request->input('device_token')) {
            $request->user()->deviceTokens()->where('token', $token)->delete();
        }

        return $this->respond(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->respond(new UserResource($request->user()));
    }

    /** Update the signed-in user's own preferences (theme, push toggle, name). */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'theme' => 'sometimes|string|in:light,dark',
            'notifications_enabled' => 'sometimes|boolean',
        ]);

        $request->user()->update($validated);

        return $this->respond(new UserResource($request->user()->fresh()), 'Profile updated');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            abort(422, 'Current password is incorrect');
        }

        $user->update(['password' => $validated['password']]);

        // Force re-login everywhere else; keep the current session's token alive
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return $this->respond(null, 'Password changed successfully');
    }
}
