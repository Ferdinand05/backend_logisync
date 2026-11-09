<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            activity('autentikasi')
                ->withProperties([
                    'email' => $request->validated('email'),
                    'ip' => $request->ip(),
                ])
                ->log('login gagal');

            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $user->tokens()->delete();

        $token = $user->createToken($request->validated('device_name', 'api'))->plainTextToken;

        activity('autentikasi')
            ->causedBy($user)
            ->withProperties([
                'device_name' => $request->validated('device_name', 'api'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('login');

        return response()->json([
            'message' => 'Login berhasil.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        activity('autentikasi')
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('logout');

        $user->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
