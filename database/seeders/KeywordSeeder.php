<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Keyword;
use App\Models\Organization;
use App\Models\SubscriberList;
use Illuminate\Database\Seeder;

class KeywordSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = Organization::all();

        foreach ($organizations as $org) {
            $lists = SubscriberList::where('organization_id', $org->id)->pluck('id');

            Keyword::factory()->count(8)->active()->create([
                'organization_id' => $org->id,
            ])->each(function (Keyword $keyword) use ($lists): void {
                if ($lists->isNotEmpty()) {
                    $keyword->lists()->attach($lists->random(1));

                    $workflow = $keyword->workflow ?? [];
                    foreach ($workflow as &$step) {
                        if ($step['type'] === 'add_to_list') {
                            $step['config']['list_id'] = $lists->random();
                        }
                    }
                    $keyword->update(['workflow' => $workflow]);
                }
            });

            Keyword::factory()->count(2)->archived()->create([
                'organization_id' => $org->id,
            ]);
        }
    }
}
