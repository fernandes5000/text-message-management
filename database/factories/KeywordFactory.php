<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class KeywordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name'            => strtoupper($this->faker->unique()->lexify('????')) . $this->faker->numberBetween(1, 99),
            'number'          => $this->faker->numerify('+1##########'),
            'status'          => $this->faker->randomElement(['active', 'active', 'active', 'archived']),
            'aliases'         => $this->faker->boolean(40) ? [$this->faker->word(), $this->faker->word()] : [],
            'workflow'        => $this->defaultWorkflow(),
            'uses_count'      => $this->faker->numberBetween(0, 500),
            'opt_ins_count'   => $this->faker->numberBetween(0, 300),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function archived(): static
    {
        return $this->state(['status' => 'archived']);
    }

    private function defaultWorkflow(): array
    {
        return [
            [
                'type'   => 'send_message',
                'config' => [
                    'message' => 'Thanks for texting! You\'ve been added to our list.',
                ],
            ],
            [
                'type'   => 'add_to_list',
                'config' => ['list_id' => null],
            ],
        ];
    }
}
