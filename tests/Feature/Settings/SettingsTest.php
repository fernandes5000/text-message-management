<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->create(['active_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->admin, ['role' => 'admin']);
});

// ---------------------------------------------------------------------------
// GET /api/v1/account/settings
// ---------------------------------------------------------------------------
describe('GET /api/v1/account/settings', function (): void {
    it('returns the active organization settings', function (): void {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/account/settings')
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'default_number', 'credits']);
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/v1/account/settings')->assertUnauthorized();
    });
});

// ---------------------------------------------------------------------------
// PUT /api/v1/account/settings
// ---------------------------------------------------------------------------
describe('PUT /api/v1/account/settings', function (): void {
    it('updates organization name and default_number', function (): void {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/account/settings', [
            'name'           => 'Updated Org Name',
            'default_number' => '555-0100',
        ])->assertOk()
            ->assertJsonPath('name', 'Updated Org Name')
            ->assertJsonPath('default_number', '555-0100');

        $this->assertDatabaseHas('organizations', [
            'id'   => $this->organization->id,
            'name' => 'Updated Org Name',
        ]);
    });

    it('requires name', function (): void {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/account/settings', ['default_number' => '555-0100'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
});

// ---------------------------------------------------------------------------
// GET /api/v1/account/members
// ---------------------------------------------------------------------------
describe('GET /api/v1/account/members', function (): void {
    it('returns members of the active organization', function (): void {
        Sanctum::actingAs($this->admin);

        $member = User::factory()->create(['active_organization_id' => $this->organization->id]);
        $this->organization->users()->attach($member, ['role' => 'member']);

        $response = $this->getJson('/api/v1/account/members')->assertOk();

        expect($response->json())->toHaveCount(2);
        expect($response->json('0.role'))->toBe('admin');
    });
});

// ---------------------------------------------------------------------------
// POST /api/v1/account/members
// ---------------------------------------------------------------------------
describe('POST /api/v1/account/members', function (): void {
    it('creates and attaches a new member', function (): void {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/account/members', [
            'name'  => 'Jane Smith',
            'email' => 'jane@example.com',
            'role'  => 'member',
        ])->assertCreated()
            ->assertJsonPath('email', 'jane@example.com')
            ->assertJsonPath('role', 'member');

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);

        $user = User::where('email', 'jane@example.com')->first();
        expect($this->organization->users()->where('users.id', $user->id)->exists())->toBeTrue();
    });

    it('returns 422 when email already exists', function (): void {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/account/members', [
            'name'  => 'Dupe',
            'email' => $this->admin->email,
            'role'  => 'member',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('requires valid role', function (): void {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/account/members', [
            'name'  => 'Test',
            'email' => 'test@example.com',
            'role'  => 'superuser',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    });
});

// ---------------------------------------------------------------------------
// DELETE /api/v1/account/members/{user}
// ---------------------------------------------------------------------------
describe('DELETE /api/v1/account/members/{user}', function (): void {
    it('removes a member from the organization', function (): void {
        Sanctum::actingAs($this->admin);

        $member = User::factory()->create(['active_organization_id' => $this->organization->id]);
        $this->organization->users()->attach($member, ['role' => 'member']);

        $this->deleteJson("/api/v1/account/members/{$member->id}")->assertNoContent();

        expect($this->organization->users()->where('users.id', $member->id)->exists())->toBeFalse();
    });

    it('cannot remove yourself', function (): void {
        Sanctum::actingAs($this->admin);

        $this->deleteJson("/api/v1/account/members/{$this->admin->id}")
            ->assertUnprocessable();
    });

    it('returns 404 when user is not in this organization', function (): void {
        Sanctum::actingAs($this->admin);

        $outsider = User::factory()->create();

        $this->deleteJson("/api/v1/account/members/{$outsider->id}")
            ->assertNotFound();
    });
});
