<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'type'            => $this->faker->unique()->randomElement([
                'planning_center', 'salesforce', 'zapier', 'mailchimp', 'hubspot',
            ]),
            'status' => 'disconnected',
            'config' => null,
        ];
    }

    public function connected(): static
    {
        return $this->state(['status' => 'connected']);
    }
}
