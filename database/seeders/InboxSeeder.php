<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Organization;
use App\Models\Subscriber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class InboxSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = Organization::all();

        foreach ($organizations as $org) {
            $subscribers = Subscriber::where('organization_id', $org->id)
                ->inRandomOrder()
                ->limit(25)
                ->get();

            foreach ($subscribers as $subscriber) {
                $conv = Conversation::create([
                    'organization_id' => $org->id,
                    'subscriber_id'   => $subscriber->id,
                    'number'          => $org->default_number ?? '+15550001234',
                    'status'          => fake()->randomElement(['open', 'open', 'done']),
                    'unread'          => fake()->boolean(35),
                    'last_message_at' => fake()->dateTimeBetween('-14 days', 'now'),
                    'last_message'    => fake()->sentence(),
                ]);

                $msgCount = fake()->numberBetween(3, 12);
                $baseTime = Carbon::instance($conv->last_message_at)->subMinutes($msgCount * 3);

                for ($i = 0; $i < $msgCount; $i++) {
                    ConversationMessage::create([
                        'conversation_id' => $conv->id,
                        'body'            => fake()->sentence(),
                        'direction'       => fake()->randomElement(['inbound', 'outbound', 'outbound']),
                        'user_id'         => null,
                        'sent_at'         => $baseTime->copy()->addMinutes($i * 3),
                    ]);
                }
            }
        }
    }
}
