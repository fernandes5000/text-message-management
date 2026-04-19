<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'body'            => $this->faker->sentence(),
            'direction'       => $this->faker->randomElement(['inbound', 'outbound']),
            'user_id'         => null,
            'sent_at'         => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    public function inbound(): static
    {
        return $this->state(['direction' => 'inbound', 'user_id' => null]);
    }

    public function outbound(): static
    {
        return $this->state(['direction' => 'outbound']);
    }
}
