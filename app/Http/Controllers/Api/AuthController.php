<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Modules\Auth\AuthService;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthService $auth): JsonResponse
    {
        $result = $auth->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return ApiResponse::success('Logged in successfully.', [
            'user' => UserResource::make($result['user'])->resolve(),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request, AuthService $auth): JsonResponse
    {
        $auth->logout($request->user());

        return ApiResponse::success('Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['role', 'tenant']);

        return ApiResponse::success('Authenticated user retrieved.', [
            'user' => UserResource::make($user)->resolve(),
        ]);
    }
}
