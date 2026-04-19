<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class PollFactory extends Factory
{
    public function definition(): array
    {
        $options = $this->faker->randomElements(
            ['Yes', 'No', 'Maybe', 'Not sure', 'Definitely', 'Absolutely not'],
            $this->faker->numberBetween(2, 4)
        );

        return [
            'organization_id' => Organization::factory(),
            'message_id'      => null,
            'question'        => $this->faker->sentence(6) . '?',
            'options'         => array_values($options),
            'active'          => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
