<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    /**
     * @return array{user: User, token: string}
     */
    public function login(string $email, string $password): array
    {
        $user = User::query()->with(['role', 'tenant'])->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            Log::warning('Failed login attempt.', ['email' => $email]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    public function authenticateWeb(string $email, string $password): User
    {
        $user = User::query()->with(['role', 'tenant'])->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            Log::warning('Failed portal login attempt.', ['email' => $email]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        Auth::login($user);

        return $user;
    }

    public function logoutWeb(): void
    {
        Auth::logout();
    }
}
