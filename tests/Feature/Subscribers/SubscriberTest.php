<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['active_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->user, ['role' => 'admin']);
});

describe('GET /api/v1/subscribers', function (): void {
    it('returns paginated subscribers for the active organization', function (): void {
        Subscriber::factory()->count(5)->for($this->organization)->create();
        Subscriber::factory()->count(3)->create(); // different org

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/subscribers')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'first_name', 'last_name', 'phone', 'status', 'source', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('filters by status', function (): void {
        Subscriber::factory()->count(3)->for($this->organization)->create(['status' => 'opted_in']);
        Subscriber::factory()->count(2)->for($this->organization)->create(['status' => 'opted_out']);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/subscribers?status=opted_in')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('searches by name or phone', function (): void {
        Subscriber::factory()->for($this->organization)->create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'phone' => '5551112222',
        ]);
        Subscriber::factory()->count(4)->for($this->organization)->create();

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/subscribers?search=Alice')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('returns 401 for unauthenticated request', function (): void {
        $this->getJson('/api/v1/subscribers')->assertUnauthorized();
    });
});

describe('POST /api/v1/subscribers', function (): void {
    it('creates a subscriber', function (): void {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/subscribers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '5551234567',
            'email' => 'john@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('first_name', 'John')
            ->assertJsonPath('phone', '5551234567');

        $this->assertDatabaseHas('subscribers', [
            'organization_id' => $this->organization->id,
            'phone' => '5551234567',
        ]);
    });

    it('returns 422 when phone is missing', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/subscribers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ])->assertUnprocessable()->assertJsonValidationErrors(['phone']);
    });

    it('returns 422 for duplicate phone within the same organization', function (): void {
        Subscriber::factory()->for($this->organization)->create(['phone' => '5551234567']);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/subscribers', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5551234567',
        ])->assertUnprocessable()->assertJsonValidationErrors(['phone']);
    });

    it('allows the same phone in a different organization', function (): void {
        $otherOrg = Organization::factory()->create();
        Subscriber::factory()->for($otherOrg)->create(['phone' => '5551234567']);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/subscribers', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5551234567',
        ])->assertCreated();
    });
});

describe('GET /api/v1/subscribers/{id}', function (): void {
    it('returns a subscriber', function (): void {
        $subscriber = Subscriber::factory()->for($this->organization)->create();

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/subscribers/{$subscriber->id}")
            ->assertOk()
            ->assertJsonPath('id', $subscriber->id);
    });

    it('returns 404 for a subscriber belonging to another organization', function (): void {
        $subscriber = Subscriber::factory()->create();

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/subscribers/{$subscriber->id}")->assertNotFound();
    });
});

describe('PUT /api/v1/subscribers/{id}', function (): void {
    it('updates a subscriber', function (): void {
        $subscriber = Subscriber::factory()->for($this->organization)->create();

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/subscribers/{$subscriber->id}", [
            'first_name' => 'Updated',
            'last_name' => $subscriber->last_name,
            'phone' => $subscriber->phone,
        ])->assertOk()->assertJsonPath('first_name', 'Updated');
    });

    it('returns 404 for a subscriber belonging to another organization', function (): void {
        $subscriber = Subscriber::factory()->create();

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/subscribers/{$subscriber->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Doe',
            'phone' => '5550000000',
        ])->assertNotFound();
    });
});

describe('DELETE /api/v1/subscribers/{id}', function (): void {
    it('deletes a subscriber', function (): void {
        $subscriber = Subscriber::factory()->for($this->organization)->create();

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/subscribers/{$subscriber->id}")->assertNoContent();

        $this->assertDatabaseMissing('subscribers', ['id' => $subscriber->id]);
    });

    it('returns 404 for a subscriber belonging to another organization', function (): void {
        $subscriber = Subscriber::factory()->create();

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/subscribers/{$subscriber->id}")->assertNotFound();
    });
});

describe('POST /api/v1/subscribers/import', function (): void {
    it('imports subscribers from a CSV file', function (): void {
        $csv = "first_name,last_name,phone,email,source\nJohn,Doe,5551111111,john@example.com,import\nJane,Doe,5552222222,,import";
        $file = UploadedFile::fake()->createWithContent('subscribers.csv', $csv);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/subscribers/import', ['file' => $file])
            ->assertOk()
            ->assertJsonStructure(['imported', 'updated', 'errors']);

        $this->assertDatabaseCount('subscribers', 2);
    });

    it('returns 422 when no file is provided', function (): void {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/subscribers/import', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });
});
