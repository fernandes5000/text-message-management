<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscriber> */
class SubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => (string) fake()->unique()->numerify('##########'),
            'email' => fake()->optional(0.7)->safeEmail(),
            'status' => fake()->randomElement(['opted_in', 'opted_in', 'opted_in', 'opted_out']),
            'source' => fake()->randomElement(['manual', 'keyword', 'import', 'api']),
        ];
    }

    public function optedIn(): static
    {
        return $this->state(['status' => 'opted_in']);
    }

    public function optedOut(): static
    {
        return $this->state(['status' => 'opted_out']);
    }
}
