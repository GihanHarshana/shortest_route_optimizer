<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_a_token(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ])
            ->assertCreated()
            ->assertJsonStructure(['message', 'user' => ['id', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'is_admin' => false]);
    }

    public function test_register_cannot_grant_admin(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Eve',
            'email' => 'eve@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'is_admin' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'eve@example.com', 'is_admin' => false]);
    }

    public function test_login_with_valid_and_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'jane@example.com', 'password' => 'secret-pass']);

        $this->postJson('/api/login', ['email' => 'jane@example.com', 'password' => 'secret-pass'])
            ->assertOk()->assertJsonStructure(['token']);

        $this->postJson('/api/login', ['email' => 'jane@example.com', 'password' => 'wrong'])
            ->assertStatus(422)->assertJsonValidationErrors(['email']);
    }
}
