<?php

namespace App\Listeners;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class LogUnauthenticatedApiRequest
{
    /**
     * Catat permintaan API yang ditolak agar mudah didiagnosis: apakah token
     * tidak dikirim sama sekali, token dikirim tetapi tidak dikenal, atau
     * token sudah kedaluwarsa.
     */
    public function handle(RequestHandled $event): void
    {
        if ($event->response->getStatusCode() !== 401 || ! $event->request->is('api/*')) {
            return;
        }

        $token = $event->request->bearerToken();

        [$tokenId, $tokenRow] = $this->resolveToken($token);

        Log::warning('Permintaan API tidak terautentikasi.', [
            'path' => $event->request->path(),
            'ip' => $event->request->ip(),
            'has_authorization_header' => $event->request->hasHeader('Authorization'),
            'has_bearer_token' => $token !== null,
            'bearer_token_id' => $tokenId,
            'token_row_found' => $tokenRow !== null,
            'token_created_at' => $tokenRow?->created_at?->toIso8601String(),
            'token_expires_at' => $tokenRow?->expires_at?->toIso8601String(),
            'user_agent' => $event->request->userAgent(),
        ]);
    }

    /**
     * @return array{0: string|null, 1: PersonalAccessToken|null}
     */
    private function resolveToken(?string $token): array
    {
        if (! is_string($token) || ! str_contains($token, '|')) {
            return [null, null];
        }

        [$tokenId] = explode('|', $token, 2);

        if (! ctype_digit($tokenId)) {
            return [$tokenId, null];
        }

        $accessTokenModel = Sanctum::$personalAccessTokenModel;

        return [
            $tokenId,
            rescue(fn () => $accessTokenModel::query()->find((int) $tokenId), report: false),
        ];
    }
}
