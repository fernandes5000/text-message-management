<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Poll;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

class PollResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'poll_id'      => Poll::factory(),
            'subscriber_id' => Subscriber::factory(),
            'option_index'  => $this->faker->numberBetween(0, 3),
        ];
    }
}
