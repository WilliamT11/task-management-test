<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_access_their_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonMissingPath('user.password');

        $this->withToken($response->json('access_token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'incorrect',
        ])->assertUnauthorized();
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $token = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('access_token');

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }
}
