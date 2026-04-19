<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create();
    $this->organization->users()->attach($this->user, ['role' => 'admin']);
});

describe('POST /api/v1/auth/login', function (): void {
    it('logs in with valid credentials', function (): void {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'locale', 'theme'],
                'organization' => ['id', 'name', 'default_number', 'credits'],
                'organizations',
            ]);
    });

    it('returns 422 with invalid credentials', function (): void {
        $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 when email is missing', function (): void {
        $this->postJson('/api/v1/auth/login', [
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 when password is missing', function (): void {
        $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('returns 422 for non-existent email', function (): void {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('POST /api/v1/auth/logout', function (): void {
    it('logs out an authenticated user', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logged out successfully.']);
    });

    it('returns 401 when unauthenticated', function (): void {
        $this->postJson('/api/v1/auth/logout')
            ->assertUnauthorized();
    });
});

describe('GET /api/v1/auth/me', function (): void {
    it('returns authenticated user with organization context', function (): void {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'locale', 'theme'],
                'organization' => ['id', 'name', 'default_number', 'credits'],
                'organizations',
            ])
            ->assertJsonPath('user.id', $this->user->id)
            ->assertJsonPath('organization.id', $this->organization->id);
    });

    it('returns 401 when unauthenticated', function (): void {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    });
});

describe('POST /api/v1/accounts/switch/{id}', function (): void {
    it('switches the active organization', function (): void {
        $other = Organization::factory()->create();
        $other->users()->attach($this->user, ['role' => 'member']);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/accounts/switch/{$other->id}")
            ->assertOk()
            ->assertJsonPath('organization.id', $other->id);
    });

    it('returns 403 when user does not belong to the organization', function (): void {
        $other = Organization::factory()->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/accounts/switch/{$other->id}")
            ->assertForbidden();
    });
});
