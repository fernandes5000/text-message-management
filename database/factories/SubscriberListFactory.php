<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SubscriberList;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SubscriberList> */
class SubscriberListFactory extends Factory
{
    protected $model = SubscriberList::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(fake()->numberBetween(1, 3), true),
            'type' => 'manual',
            'sync_source' => null,
            'last_synced_at' => null,
        ];
    }
}
