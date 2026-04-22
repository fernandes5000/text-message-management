<?php

declare(strict_types=1);

use App\Events\NewConversationMessage;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Organization;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['active_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->user, ['role' => 'admin']);
});

describe('GET /api/v1/conversations', function (): void {
    it('returns paginated conversations for the active organization', function (): void {
        Subscriber::factory()->count(5)->for($this->organization)->create()->each(
            fn ($sub) => Conversation::factory()->create([
                'organization_id' => $this->organization->id,
                'subscriber_id'   => $sub->id,
            ])
        );
        Conversation::factory()->count(3)->create(); // different org

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'status', 'unread', 'last_message', 'subscriber']],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    });

    it('filters by status', function (): void {
        $sub1 = Subscriber::factory()->for($this->organization)->create();
        $sub2 = Subscriber::factory()->for($this->organization)->create();

        Conversation::factory()->open()->create(['organization_id' => $this->organization->id, 'subscriber_id' => $sub1->id]);
        Conversation::factory()->done()->create(['organization_id' => $this->organization->id, 'subscriber_id' => $sub2->id]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/conversations?status=open')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filters unread', function (): void {
        $sub1 = Subscriber::factory()->for($this->organization)->create();
        $sub2 = Subscriber::factory()->for($this->organization)->create();

        Conversation::factory()->create(['organization_id' => $this->organization->id, 'subscriber_id' => $sub1->id, 'unread' => true]);
        Conversation::factory()->create(['organization_id' => $this->organization->id, 'subscriber_id' => $sub2->id, 'unread' => false]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/conversations?unread=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/v1/conversations')->assertUnauthorized();
    });
});

describe('GET /api/v1/conversations/{conversation}', function (): void {
    it('returns conversation with messages and marks it as read', function (): void {
        $subscriber = Subscriber::factory()->for($this->organization)->create();
        $conv = Conversation::factory()->create([
            'organization_id' => $this->organization->id,
            'subscriber_id'   => $subscriber->id,
            'unread'          => true,
        ]);
        ConversationMessage::factory()->count(3)->create(['conversation_id' => $conv->id]);

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/conversations/{$conv->id}")
            ->assertOk()
            ->assertJsonPath('unread', false)
            ->assertJsonCount(3, 'messages');
    });

    it('denies access to another org conversation', function (): void {
        $other = Conversation::factory()->create();

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/conversations/{$other->id}")->assertForbidden();
    });
});

describe('POST /api/v1/conversations/{conversation}/reply', function (): void {
    it('creates an outbound message and broadcasts event', function (): void {
        Event::fake([NewConversationMessage::class]);

        $subscriber = Subscriber::factory()->for($this->organization)->create();
        $conv = Conversation::factory()->create([
            'organization_id' => $this->organization->id,
            'subscriber_id'   => $subscriber->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/conversations/{$conv->id}/reply", [
            'body' => 'Hello there!',
        ])->assertCreated()
          ->assertJsonFragment(['direction' => 'outbound', 'body' => 'Hello there!']);

        Event::assertDispatched(NewConversationMessage::class);
    });

    it('validates body is required', function (): void {
        $subscriber = Subscriber::factory()->for($this->organization)->create();
        $conv = Conversation::factory()->create([
            'organization_id' => $this->organization->id,
            'subscriber_id'   => $subscriber->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/conversations/{$conv->id}/reply", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    });
});

describe('PATCH /api/v1/conversations/{conversation}/done', function (): void {
    it('marks a conversation as done', function (): void {
        $subscriber = Subscriber::factory()->for($this->organization)->create();
        $conv = Conversation::factory()->open()->create([
            'organization_id' => $this->organization->id,
            'subscriber_id'   => $subscriber->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->patchJson("/api/v1/conversations/{$conv->id}/done")
            ->assertOk()
            ->assertJsonPath('status', 'done');
    });
});

describe('PATCH /api/v1/conversations/{conversation}/unread', function (): void {
    it('marks a conversation as unread', function (): void {
        $subscriber = Subscriber::factory()->for($this->organization)->create();
        $conv = Conversation::factory()->done()->create([
            'organization_id' => $this->organization->id,
            'subscriber_id'   => $subscriber->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->patchJson("/api/v1/conversations/{$conv->id}/unread")
            ->assertOk()
            ->assertJsonPath('unread', true);
    });
});
