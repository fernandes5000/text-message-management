<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\SubscriberList;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriberSeeder extends Seeder
{
    private const TOTAL = 4500;
    private const CHUNK = 500;

    private const LIST_NAMES = [
        'All Members',
        'College Group',
        'Prayer Team',
        'Volunteers',
        'New Visitors',
        'Youth Ministry',
        'Women\'s Bible Study',
        'Men\'s Fellowship',
    ];

    private const SOURCES = ['keyword', 'import', 'manual', 'api'];
    private const STATUSES = ['opted_in', 'opted_in', 'opted_in', 'opted_out'];
    private const FIRST_NAMES = [
        'James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph',
        'Thomas', 'Charles', 'Mary', 'Patricia', 'Jennifer', 'Linda', 'Barbara', 'Elizabeth',
        'Susan', 'Jessica', 'Sarah', 'Karen', 'Lisa', 'Nancy', 'Betty', 'Dorothy',
        'Sandra', 'Ashley', 'Emily', 'Donna', 'Michelle', 'Carol', 'Amanda', 'Melissa',
        'Deborah', 'Stephanie', 'Rebecca', 'Sharon', 'Laura', 'Cynthia', 'Kathleen', 'Amy',
    ];
    private const LAST_NAMES = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
        'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
        'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson',
        'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson',
    ];

    public function run(): void
    {
        $main = Organization::where('name', 'Acme Corp')->first();
        if (! $main) {
            return;
        }

        // Create lists for the main org
        $lists = [];
        foreach (self::LIST_NAMES as $name) {
            $lists[] = SubscriberList::firstOrCreate(
                ['organization_id' => $main->id, 'name' => $name],
                ['type' => 'manual']
            );
        }

        // Seed subscribers in bulk
        $now = now()->toDateTimeString();
        $phoneBase = 5550000000;
        $chunks = (int) ceil(self::TOTAL / self::CHUNK);

        for ($c = 0; $c < $chunks; $c++) {
            $rows = [];
            $count = ($c === $chunks - 1) ? self::TOTAL - ($c * self::CHUNK) : self::CHUNK;

            for ($i = 0; $i < $count; $i++) {
                $index = ($c * self::CHUNK) + $i;
                $rows[] = [
                    'organization_id' => $main->id,
                    'first_name' => self::FIRST_NAMES[$index % count(self::FIRST_NAMES)],
                    'last_name' => self::LAST_NAMES[$index % count(self::LAST_NAMES)],
                    'phone' => (string) ($phoneBase + $index),
                    'email' => 'user' . $index . '@example.com',
                    'status' => self::STATUSES[$index % count(self::STATUSES)],
                    'source' => self::SOURCES[$index % count(self::SOURCES)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('subscribers')->insertOrIgnore($rows);
        }

        // Distribute subscribers across lists
        $subscriberIds = DB::table('subscribers')
            ->where('organization_id', $main->id)
            ->pluck('id')
            ->toArray();

        foreach ($lists as $index => $list) {
            $slice = array_slice($subscriberIds, $index * 400, 600);
            $pivotRows = array_map(
                fn (int $subId) => ['list_id' => $list->id, 'subscriber_id' => $subId],
                $slice
            );

            foreach (array_chunk($pivotRows, 500) as $chunk) {
                DB::table('list_subscriber')->insertOrIgnore($chunk);
            }
        }
    }
}
