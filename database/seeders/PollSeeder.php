<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Poll;
use App\Models\PollResponse;
use App\Models\Subscriber;
use Illuminate\Database\Seeder;

class PollSeeder extends Seeder
{
    private const POLLS = [
        [
            'question' => 'Will you be attending the upcoming Sunday service?',
            'options'  => ['Yes, I\'ll be there', 'No, I can\'t make it', 'Maybe'],
        ],
        [
            'question' => 'How would you rate our last event?',
            'options'  => ['Excellent', 'Good', 'Average', 'Needs improvement'],
        ],
        [
            'question' => 'Would you like to join a small group?',
            'options'  => ['Yes, sign me up!', 'I\'m already in one', 'Not right now', 'Tell me more first'],
        ],
        [
            'question' => 'Which service time works best for you?',
            'options'  => ['9:00 AM', '11:00 AM', '5:00 PM', 'Any time works'],
        ],
        [
            'question' => 'Are you interested in volunteering?',
            'options'  => ['Yes, very interested', 'Maybe someday', 'No, thank you'],
        ],
    ];

    public function run(): void
    {
        $organizations = Organization::all();

        foreach ($organizations as $org) {
            $subscribers = Subscriber::where('organization_id', $org->id)->inRandomOrder()->limit(200)->get();

            foreach (self::POLLS as $i => $data) {
                $poll = Poll::create([
                    'organization_id' => $org->id,
                    'question'        => $data['question'],
                    'options'         => $data['options'],
                    'active'          => $i < 4,
                ]);

                // Seed realistic response distribution
                $respondents = $subscribers->random(min((int) ($subscribers->count() * 0.4), 80));
                foreach ($respondents as $subscriber) {
                    PollResponse::create([
                        'poll_id'      => $poll->id,
                        'subscriber_id' => $subscriber->id,
                        'option_index'  => random_int(0, count($data['options']) - 1),
                    ]);
                }
            }
        }
    }
}
