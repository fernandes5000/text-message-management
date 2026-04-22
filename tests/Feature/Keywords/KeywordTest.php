<?php

declare(strict_types=1);

use App\Models\Keyword;
use App\Models\Organization;
use App\Models\SubscriberList;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['active_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->user, ['role' => 'admin']);
});

describe('GET /api/v1/keywords', function (): void {
    it('returns paginated keywords for the active organization', function (): void {
        Keyword::factory()->count(5)->active()->create(['organization_id' => $this->organization->id]);
        Keyword::factory()->count(3)->create(); // different org

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/keywords')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'status', 'uses_count', 'opt_ins_count', 'workflow', 'aliases']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('filters by status', function (): void {
        Keyword::factory()->count(4)->active()->create(['organization_id' => $this->organization->id]);
        Keyword::factory()->count(2)->archived()->create(['organization_id' => $this->organization->id]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/keywords?status=active')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/v1/keywords')->assertUnauthorized();
    });
});

describe('POST /api/v1/keywords', function (): void {
    it('creates a keyword and uppercases the name', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/keywords', [
            'name'   => 'join',
            'number' => '+15550001234',
        ])->assertCreated()
          ->assertJsonFragment(['name' => 'JOIN']);
    });

    it('attaches lists to the keyword', function (): void {
        $lists = SubscriberList::factory()->count(2)->create(['organization_id' => $this->organization->id]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/keywords', [
            'name'     => 'VIP',
            'list_ids' => $lists->pluck('id')->toArray(),
        ])->assertCreated();

        expect($response->json('lists'))->toHaveCount(2);
    });

    it('saves workflow steps', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/keywords', [
            'name'     => 'SALE',
            'workflow' => [
                ['type' => 'send_message', 'config' => ['message' => 'Thanks!']],
                ['type' => 'add_to_list',  'config' => ['list_id' => null]],
            ],
        ])->assertCreated()
          ->assertJsonFragment(['type' => 'send_message']);
    });

    it('validates required fields', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/keywords', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('rejects keyword names with spaces', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/keywords', ['name' => 'TWO WORDS'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
});

describe('GET /api/v1/keywords/{keyword}', function (): void {
    it('returns a keyword with lists', function (): void {
        $keyword = Keyword::factory()->create(['organization_id' => $this->organization->id]);

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/keywords/{$keyword->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => $keyword->id]);
    });

    it('denies access to keywords from another org', function (): void {
        $other = Keyword::factory()->create();

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/keywords/{$other->id}")->assertForbidden();
    });
});

describe('PUT /api/v1/keywords/{keyword}', function (): void {
    it('updates a keyword', function (): void {
        $keyword = Keyword::factory()->create(['organization_id' => $this->organization->id]);

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/keywords/{$keyword->id}", ['status' => 'archived'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'archived']);
    });

    it('updates workflow', function (): void {
        $keyword = Keyword::factory()->create(['organization_id' => $this->organization->id]);

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/keywords/{$keyword->id}", [
            'workflow' => [
                ['type' => 'send_message', 'config' => ['message' => 'Updated reply']],
            ],
        ])->assertOk()
          ->assertJsonFragment(['message' => 'Updated reply']);
    });
});

describe('DELETE /api/v1/keywords/{keyword}', function (): void {
    it('deletes a keyword', function (): void {
        $keyword = Keyword::factory()->create(['organization_id' => $this->organization->id]);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/keywords/{$keyword->id}")->assertOk();
        $this->assertDatabaseMissing('keywords', ['id' => $keyword->id]);
    });
});
