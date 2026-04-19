<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Message;
use App\Models\Organization;
use App\Models\SubscriberList;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = Organization::all();
        $user = User::first();

        foreach ($organizations as $org) {
            $lists = SubscriberList::where('organization_id', $org->id)->pluck('id');

            // Sent messages
            Message::factory()->count(20)->sent()->create([
                'organization_id' => $org->id,
                'created_by'      => $user->id,
            ])->each(function (Message $msg) use ($lists): void {
                if ($lists->isNotEmpty()) {
                    $msg->lists()->attach($lists->random(min(2, $lists->count())));
                }
            });

            // Scheduled
            Message::factory()->count(4)->scheduled()->create([
                'organization_id' => $org->id,
                'created_by'      => $user->id,
            ])->each(function (Message $msg) use ($lists): void {
                if ($lists->isNotEmpty()) {
                    $msg->lists()->attach($lists->random(1));
                }
            });

            // Drafts
            Message::factory()->count(3)->draft()->create([
                'organization_id' => $org->id,
                'created_by'      => $user->id,
            ]);
        }
    }
}
