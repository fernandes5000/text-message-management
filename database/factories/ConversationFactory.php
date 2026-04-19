<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'subscriber_id'   => Subscriber::factory(),
            'number'          => $this->faker->numerify('+1##########'),
            'status'          => $this->faker->randomElement(['open', 'open', 'open', 'done']),
            'unread'          => $this->faker->boolean(30),
            'last_message_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'last_message'    => $this->faker->sentence(),
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => 'open']);
    }

    public function done(): static
    {
        return $this->state(['status' => 'done', 'unread' => false]);
    }
}
