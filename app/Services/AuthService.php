<?php
namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }

    public function login(array $credentials, string $deviceName = 'auth-token') {
        $user = $this->userRepository->findByEmail($credentials['email']);
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            abort(401, 'Invalid credentials');
        }

        if (!$user->active) {
            abort(403, 'User account is inactive');
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'user' => clone $user,
            'token' => $token
        ];
    }
}