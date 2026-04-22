<?php

declare(strict_types=1);

use App\Models\Integration;
use App\Models\Organization;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['active_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->user, ['role' => 'admin']);
});

describe('GET /api/v1/integrations', function (): void {
    it('returns integrations for the active organization', function (): void {
        Integration::factory()->count(3)->create(['organization_id' => $this->organization->id]);
        Integration::factory()->count(2)->create(); // different org

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/integrations')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonStructure([['id', 'type', 'status']]);
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/v1/integrations')->assertUnauthorized();
    });
});

describe('POST /api/v1/integrations/{integration}/connect', function (): void {
    it('sets integration status to connected', function (): void {
        $integration = Integration::factory()->create([
            'organization_id' => $this->organization->id,
            'status'          => 'disconnected',
        ]);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/integrations/{$integration->id}/connect")
            ->assertOk()
            ->assertJsonPath('status', 'connected');
    });

    it('denies connecting another org integration', function (): void {
        $integration = Integration::factory()->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/integrations/{$integration->id}/connect")->assertForbidden();
    });
});

describe('POST /api/v1/integrations/{integration}/disconnect', function (): void {
    it('sets integration status to disconnected', function (): void {
        $integration = Integration::factory()->connected()->create([
            'organization_id' => $this->organization->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/integrations/{$integration->id}/disconnect")
            ->assertOk()
            ->assertJsonPath('status', 'disconnected');
    });
});
