<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        $status = $this->faker->randomElement(['draft', 'scheduled', 'sent', 'failed']);
        $sendType = $this->faker->randomElement(['now', 'scheduled', 'recurring']);

        return [
            'organization_id' => Organization::factory(),
            'created_by'      => User::factory(),
            'name'            => $this->faker->sentence(4),
            'body'            => $this->faker->paragraph(),
            'status'          => $status,
            'send_type'       => $sendType,
            'scheduled_at'    => $sendType === 'scheduled' ? $this->faker->dateTimeBetween('now', '+30 days') : null,
            'recurrence'      => $sendType === 'recurring' ? $this->faker->randomElement(['daily', 'weekly', 'monthly']) : null,
            'from_number'     => $this->faker->numerify('+1##########'),
            'use_header'      => $this->faker->boolean(30),
            'header'          => null,
            'media_url'       => null,
            'recipient_count' => $status === 'sent' ? $this->faker->numberBetween(10, 5000) : 0,
            'credits_used'    => $status === 'sent' ? $this->faker->numberBetween(10, 5000) : 0,
            'sent_at'         => $status === 'sent' ? $this->faker->dateTimeBetween('-90 days', 'now') : null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'send_type' => 'now']);
    }

    public function scheduled(): static
    {
        return $this->state([
            'status'       => 'scheduled',
            'send_type'    => 'scheduled',
            'scheduled_at' => now()->addDays(rand(1, 14)),
        ]);
    }

    public function sent(): static
    {
        return $this->state([
            'status'          => 'sent',
            'send_type'       => 'now',
            'sent_at'         => now()->subDays(rand(1, 90)),
            'recipient_count' => rand(50, 2000),
            'credits_used'    => rand(50, 2000),
        ]);
    }
}
