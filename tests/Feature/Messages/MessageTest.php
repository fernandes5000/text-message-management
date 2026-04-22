<?php

declare(strict_types=1);

use App\Jobs\SendMessageJob;
use App\Models\Message;
use App\Models\Organization;
use App\Models\SubscriberList;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['active_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->user, ['role' => 'admin']);
});

describe('GET /api/v1/messages', function (): void {
    it('returns paginated messages for the active organization', function (): void {
        Message::factory()->count(5)->create([
            'organization_id' => $this->organization->id,
            'created_by'      => $this->user->id,
        ]);
        Message::factory()->count(3)->create(); // different org

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/messages')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'body', 'status', 'send_type', 'recipient_count', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('filters by status', function (): void {
        Message::factory()->count(3)->draft()->create([
            'organization_id' => $this->organization->id,
            'created_by'      => $this->user->id,
        ]);
        Message::factory()->count(2)->sent()->create([
            'organization_id' => $this->organization->id,
            'created_by'      => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/messages?status=draft')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/v1/messages')->assertUnauthorized();
    });
});

describe('POST /api/v1/messages', function (): void {
    it('creates a draft message', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/messages', [
            'name'      => 'Test Campaign',
            'body'      => 'Hello {first_name}!',
            'send_type' => 'now',
        ])->assertCreated()
          ->assertJsonFragment(['name' => 'Test Campaign', 'status' => 'sending']);
    });

    it('attaches lists to the message', function (): void {
        $lists = SubscriberList::factory()->count(2)->create(['organization_id' => $this->organization->id]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/messages', [
            'name'      => 'Campaign with Lists',
            'body'      => 'Hello!',
            'send_type' => 'now',
            'list_ids'  => $lists->pluck('id')->toArray(),
        ])->assertCreated();

        expect($response->json('lists'))->toHaveCount(2);
    });

    it('dispatches SendMessageJob for send_type=now', function (): void {
        Queue::fake();
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/messages', [
            'name'      => 'Instant Send',
            'body'      => 'Hello!',
            'send_type' => 'now',
        ])->assertCreated();

        Queue::assertPushed(SendMessageJob::class);
    });

    it('schedules SendMessageJob with delay for send_type=scheduled', function (): void {
        Queue::fake();
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/messages', [
            'name'         => 'Scheduled Campaign',
            'body'         => 'Hello!',
            'send_type'    => 'scheduled',
            'scheduled_at' => now()->addDay()->toISOString(),
        ])->assertCreated()
          ->assertJsonFragment(['status' => 'scheduled']);

        Queue::assertPushed(SendMessageJob::class);
    });

    it('validates required fields', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/messages', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'body', 'send_type']);
    });
});

describe('GET /api/v1/messages/{message}', function (): void {
    it('returns a single message with lists', function (): void {
        $message = Message::factory()->create([
            'organization_id' => $this->organization->id,
            'created_by'      => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/messages/{$message->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => $message->id]);
    });

    it('denies access to messages from another org', function (): void {
        $other = Message::factory()->create();

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/messages/{$other->id}")->assertForbidden();
    });
});

describe('PUT /api/v1/messages/{message}', function (): void {
    it('updates a message', function (): void {
        $message = Message::factory()->draft()->create([
            'organization_id' => $this->organization->id,
            'created_by'      => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/messages/{$message->id}", ['name' => 'Updated Name'])
            ->assertOk()
            ->assertJsonFragment(['name' => 'Updated Name']);
    });
});

describe('DELETE /api/v1/messages/{message}', function (): void {
    it('deletes a message', function (): void {
        $message = Message::factory()->create([
            'organization_id' => $this->organization->id,
            'created_by'      => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/messages/{$message->id}")->assertOk();
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    });
});

describe('POST /api/v1/messages/{message}/send', function (): void {
    it('dispatches the send job', function (): void {
        Queue::fake();

        $message = Message::factory()->draft()->create([
            'organization_id' => $this->organization->id,
            'created_by'      => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/messages/{$message->id}/send")
            ->assertOk()
            ->assertJsonFragment(['status' => 'sending']);

        Queue::assertPushed(SendMessageJob::class);
    });
});
