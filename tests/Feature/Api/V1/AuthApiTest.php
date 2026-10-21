<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_profile_requires_a_bearer_token_and_returns_safe_user_data(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);

        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.telegram_link_code')
            ->assertJsonMissingPath('data.remember_token');
    }

    public function test_login_issues_a_bearer_token_and_returns_expiration_and_safe_profile(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'device_name' => 'android-phone',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.telegram_link_code');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'android-phone',
        ]);

        $this->assertNotEmpty($response->json('data.access_token'));
        $this->assertNotEmpty($response->json('data.expires_at'));
    }

    public function test_invalid_credentials_receive_a_generic_json_error(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'android-phone',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_inactive_users_cannot_login_or_use_existing_tokens(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'device_name' => 'android-phone',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');

        $token = $user->createToken('existing-device')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'INACTIVE_USER');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $firstToken = $user->createToken('first-device');
        $secondToken = $user->createToken('second-device');

        $this->withToken($firstToken->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.message', 'Logged out successfully.');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $firstToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $secondToken->accessToken->id]);

        // Feature tests reuse the same guard instance across requests, unlike separate HTTP requests.
        $this->app['auth']->forgetGuards();

        $this->withToken($firstToken->plainTextToken)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'missing@example.com',
                'password' => 'wrong-password',
                'device_name' => 'android-phone',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
            'device_name' => 'android-phone',
        ])->assertTooManyRequests();
    }

    public function test_web_login_page_remains_available(): void
    {
        $this->get('/login')->assertOk();
    }
}
