<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_returned_from_login_can_access_protected_endpoint(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'password' => 'password',
            'role' => 'gudang',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'bruno',
        ])->assertCreated();

        $token = $response->json('data.token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', 'gudang');
    }

    public function test_login_revokes_the_previous_token(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'password' => 'password',
        ]);

        $firstToken = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'bruno',
        ])->assertCreated()->json('data.token');

        $secondToken = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'bruno',
        ])->assertCreated()->json('data.token');

        $this->withToken($firstToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();

        $this->withToken($secondToken)
            ->getJson('/api/auth/me')
            ->assertOk();
    }

    public function test_unauthenticated_api_request_is_logged_with_header_diagnostics(): void
    {
        Log::spy();

        $this->getJson('/api/auth/me')->assertUnauthorized();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context): bool => $context['path'] === 'api/auth/me'
                && $context['has_authorization_header'] === false
                && $context['has_bearer_token'] === false);
    }

    public function test_rejected_bearer_token_is_logged_as_sent(): void
    {
        Log::spy();

        $this->withToken('999|token-yang-tidak-ada')
            ->getJson('/api/auth/me')
            ->assertUnauthorized();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context): bool => $context['has_authorization_header'] === true
                && $context['has_bearer_token'] === true
                && $context['bearer_token_id'] === '999'
                && $context['token_row_found'] === false);
    }
}
