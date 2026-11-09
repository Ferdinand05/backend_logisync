<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->string('email')->toString();
        $deviceName = $request->string('device_name')->toString() ?: 'api';

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            $this->recordAuthenticationActivity('login gagal', null, [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $token = DB::transaction(function () use ($user, $deviceName): string {
            $user->tokens()->delete();

            return $user->createToken($deviceName)->plainTextToken;
        });

        $this->recordAuthenticationActivity('login', $user, [
            'device_name' => $deviceName,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

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
        $accessToken = $user?->currentAccessToken();

        $this->recordAuthenticationActivity('logout', $user, [
            'ip' => $request->ip(),
        ]);

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Catat aktivitas autentikasi tanpa pernah menggagalkan proses login/logout.
     *
     * @param  array<string, mixed>  $properties
     */
    private function recordAuthenticationActivity(string $description, ?User $causer, array $properties): void
    {
        try {
            $activity = activity('autentikasi')->withProperties($properties);

            if ($causer !== null) {
                $activity->causedBy($causer);
            }

            $activity->log($description);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
