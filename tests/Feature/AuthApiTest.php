<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_login_returns_token_and_user_resource(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'password',
            'device_name' => 'ionic',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Login berhasil.')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'budi@example.com')
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('ionic', $user->tokens()->first()->name);
    }

    public function test_login_replaces_existing_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@example.com',
            'password' => 'password',
        ]);
        $user->createToken('lama-1');
        $user->createToken('lama-2');

        $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'password',
            'device_name' => 'perangkat-baru',
        ])->assertCreated();

        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('perangkat-baru', $user->tokens()->first()->name);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'budi@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'salah',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah.');
    }

    public function test_login_validates_the_request(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonMissingPath('data.password');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil.');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->travel(25)->hours();

        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }
}
