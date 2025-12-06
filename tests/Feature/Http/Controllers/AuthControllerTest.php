<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_register(): void
    {
        $response = $this->postJson(route('auths.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'timezone' => 'Europe/Kyiv',
            'language' => 'uk',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ])
            ->assertJson([
                'user' => [
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_user_can_register_with_null_timezone(): void
    {
        $response = $this->postJson(route('auths.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'timezone' => null,
            'language' => 'uk',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ])
            ->assertJson([
                'user' => [
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_registration_validates_required_fields(): void
    {
        $response = $this->postJson(route('auths.register'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_validates_email_uniqueness(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson(route('auths.register'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_validates_password_minimum_length(): void
    {
        $response = $this->postJson(route('auths.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_validates_timezone(): void
    {
        $response = $this->postJson(route('auths.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'timezone' => 'Invalid/Timezone',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['timezone']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson(route('auths.login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ])
            ->assertJson([
                'user' => [
                    'email' => 'test@example.com',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'correctpassword',
        ]);

        $response = $this->postJson(route('auths.login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson(route('auths.login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson(route('auths.logout'))
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->deleteJson(route('auths.logout'));

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAsUser($user)
            ->getJson(route('auths.get-user'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'timezone', 'language'],
            ])
            ->assertJson([
                'data' => [
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                ],
            ]);
    }

    public function test_get_profile_requires_authentication(): void
    {
        $response = $this->getJson(route('auths.get-user'));

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAsUser($user)
            ->patchJson(route('auths.update-user'), [
                'name' => 'New Name',
                'timezone' => 'America/New_York',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email'],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'New Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'timezone' => 'America/New_York',
        ]);
    }

    public function test_update_profile_validates_unique_email(): void
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $currentUser = User::factory()->create(['email' => 'current@example.com']);

        $response = $this->actingAsUser($currentUser)
            ->patchJson(route('auths.update-user'), [
                'email' => 'existing@example.com',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_profile_requires_authentication(): void
    {
        $response = $this->patchJson(route('auths.update-user'), [
            'name' => 'New Name',
        ]);

        $response->assertUnauthorized();
    }
}
