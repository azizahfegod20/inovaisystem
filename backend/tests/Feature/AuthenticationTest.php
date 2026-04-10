<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ana Silva',
            'email' => 'ana@email.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.name', 'Ana Silva')
            ->assertJsonPath('user.email', 'ana@email.com');

        $this->assertDatabaseHas('users', ['email' => 'ana@email.com']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@email.com',
            'password' => bcrypt('senha12345'),
        ]);

        $response = $this->withSession([])->postJson('/api/auth/login', [
            'email' => 'ana@email.com',
            'password' => 'senha12345',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'ana@email.com');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'ana@email.com',
            'password' => bcrypt('senha12345'),
        ]);

        $response = $this->withSession([])->postJson('/api/auth/login', [
            'email' => 'ana@email.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession([])
            ->postJson('/api/auth/logout');

        $response->assertStatus(204);
    }

    public function test_user_can_get_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonPath('email', $user->email);
    }

    public function test_unauthenticated_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/auth/user');
        $response->assertStatus(401);
    }

    public function test_access_denied_without_company_selected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession([])
            ->getJson('/api/invoices');

        $response->assertStatus(403);
    }

    public function test_company_selection_works(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $user->companies()->attach($company->id, ['role' => 'admin']);

        $response = $this->actingAs($user)
            ->withSession([])
            ->postJson("/api/companies/{$company->id}/select");

        $response->assertStatus(200)
            ->assertJsonPath('company_id', $company->id);
    }

    public function test_register_requires_valid_data(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'invalid',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertStatus(422);
    }
}
