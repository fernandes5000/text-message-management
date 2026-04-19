<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Poll;
use App\Models\PollResponse;
use App\Models\Subscriber;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['active_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->user, ['role' => 'admin']);
});

describe('GET /api/v1/polls', function (): void {
    it('returns paginated polls for the active organization', function (): void {
        Poll::factory()->count(3)->create(['organization_id' => $this->organization->id]);
        Poll::factory()->count(2)->create(); // different org

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/polls')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'question', 'options', 'active', 'response_counts', 'total_responses']],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/v1/polls')->assertUnauthorized();
    });
});

describe('POST /api/v1/polls', function (): void {
    it('creates a poll', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/polls', [
            'question' => 'Will you attend?',
            'options'  => ['Yes', 'No', 'Maybe'],
        ])->assertCreated()
          ->assertJsonPath('question', 'Will you attend?')
          ->assertJsonCount(3, 'options');
    });

    it('validates minimum two options', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/polls', [
            'question' => 'Will you attend?',
            'options'  => ['Yes'],
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['options']);
    });

    it('validates question is required', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/polls', ['options' => ['Yes', 'No']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['question']);
    });
});

describe('GET /api/v1/polls/{poll}', function (): void {
    it('returns poll with response counts', function (): void {
        $subscriber = Subscriber::factory()->for($this->organization)->create();
        $poll = Poll::factory()->create([
            'organization_id' => $this->organization->id,
            'options'         => ['Yes', 'No', 'Maybe'],
        ]);
        PollResponse::factory()->create([
            'poll_id'       => $poll->id,
            'subscriber_id' => $subscriber->id,
            'option_index'  => 0,
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/polls/{$poll->id}")
            ->assertOk()
            ->assertJsonPath('total_responses', 1)
            ->assertJsonPath('response_counts.0', 1);
    });

    it('denies access to another org poll', function (): void {
        $poll = Poll::factory()->create();

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/polls/{$poll->id}")->assertForbidden();
    });
});

describe('DELETE /api/v1/polls/{poll}', function (): void {
    it('deletes a poll', function (): void {
        $poll = Poll::factory()->create(['organization_id' => $this->organization->id]);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/polls/{$poll->id}")->assertNoContent();

        $this->assertDatabaseMissing('polls', ['id' => $poll->id]);
    });

    it('denies deleting another org poll', function (): void {
        $poll = Poll::factory()->create();

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/polls/{$poll->id}")->assertForbidden();
    });
});
