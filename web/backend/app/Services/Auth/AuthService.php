<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const TOKEN_NAME = 'frontend-token';

    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(array $data): array
    {
        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $currentToken = $user->currentAccessToken();

        if (!$currentToken) {
            return;
        }

        $user->tokens()
            ->where('id', $currentToken->id)
            ->delete();
    }
}