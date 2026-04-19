<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Subscriber;
use App\Models\SubscriberList;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['active_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->user, ['role' => 'admin']);
});

describe('GET /api/v1/lists', function (): void {
    it('returns all lists for the active organization', function (): void {
        SubscriberList::factory()->count(4)->for($this->organization)->create();
        SubscriberList::factory()->count(2)->create(); // different org

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/lists')
            ->assertOk()
            ->assertJsonCount(4);
    });

    it('returns 401 for unauthenticated request', function (): void {
        $this->getJson('/api/v1/lists')->assertUnauthorized();
    });
});

describe('POST /api/v1/lists', function (): void {
    it('creates a list', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/lists', ['name' => 'Newsletter', 'type' => 'manual'])
            ->assertCreated()
            ->assertJsonPath('name', 'Newsletter');

        $this->assertDatabaseHas('lists', [
            'organization_id' => $this->organization->id,
            'name' => 'Newsletter',
        ]);
    });

    it('returns 422 when name is missing', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/lists', ['type' => 'manual'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
});

describe('GET /api/v1/lists/{id}', function (): void {
    it('returns a list with subscriber count', function (): void {
        $list = SubscriberList::factory()->for($this->organization)->create();
        $subscribers = Subscriber::factory()->count(3)->for($this->organization)->create();
        $list->subscribers()->attach($subscribers);

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/lists/{$list->id}")
            ->assertOk()
            ->assertJsonPath('id', $list->id)
            ->assertJsonPath('subscribers_count', 3);
    });

    it('returns 404 for a list belonging to another organization', function (): void {
        $list = SubscriberList::factory()->create();

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/lists/{$list->id}")->assertNotFound();
    });
});

describe('PUT /api/v1/lists/{id}', function (): void {
    it('updates a list', function (): void {
        $list = SubscriberList::factory()->for($this->organization)->create();

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/lists/{$list->id}", ['name' => 'Updated Name', 'type' => 'manual'])
            ->assertOk()
            ->assertJsonPath('name', 'Updated Name');
    });

    it('returns 404 for a list belonging to another organization', function (): void {
        $list = SubscriberList::factory()->create();

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/lists/{$list->id}", ['name' => 'X', 'type' => 'manual'])
            ->assertNotFound();
    });
});

describe('DELETE /api/v1/lists/{id}', function (): void {
    it('deletes a list', function (): void {
        $list = SubscriberList::factory()->for($this->organization)->create();

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/lists/{$list->id}")->assertNoContent();

        $this->assertDatabaseMissing('lists', ['id' => $list->id]);
    });

    it('returns 404 for a list belonging to another organization', function (): void {
        $list = SubscriberList::factory()->create();

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/lists/{$list->id}")->assertNotFound();
    });
});

describe('POST /api/v1/lists/{id}/subscribers', function (): void {
    it('adds a subscriber to a list', function (): void {
        $list = SubscriberList::factory()->for($this->organization)->create();
        $subscriber = Subscriber::factory()->for($this->organization)->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/lists/{$list->id}/subscribers", [
            'subscriber_id' => $subscriber->id,
        ])->assertCreated();

        $this->assertDatabaseHas('list_subscriber', [
            'list_id' => $list->id,
            'subscriber_id' => $subscriber->id,
        ]);
    });

    it('returns 422 when subscriber_id is missing', function (): void {
        $list = SubscriberList::factory()->for($this->organization)->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/lists/{$list->id}/subscribers", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subscriber_id']);
    });
});

describe('DELETE /api/v1/lists/{id}/subscribers/{subscriberId}', function (): void {
    it('removes a subscriber from a list', function (): void {
        $list = SubscriberList::factory()->for($this->organization)->create();
        $subscriber = Subscriber::factory()->for($this->organization)->create();
        $list->subscribers()->attach($subscriber);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/lists/{$list->id}/subscribers/{$subscriber->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('list_subscriber', [
            'list_id' => $list->id,
            'subscriber_id' => $subscriber->id,
        ]);
    });
});
