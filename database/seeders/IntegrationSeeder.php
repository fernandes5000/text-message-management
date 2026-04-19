<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Integration;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class IntegrationSeeder extends Seeder
{
    private const INTEGRATIONS = [
        [
            'type'   => 'planning_center',
            'status' => 'connected',
            'config' => ['app_id' => 'demo_app_id', 'list_sync' => true],
        ],
        [
            'type'   => 'salesforce',
            'status' => 'disconnected',
            'config' => null,
        ],
        [
            'type'   => 'zapier',
            'status' => 'connected',
            'config' => ['webhook_url' => 'https://hooks.zapier.com/demo/12345'],
        ],
        [
            'type'   => 'mailchimp',
            'status' => 'disconnected',
            'config' => null,
        ],
        [
            'type'   => 'hubspot',
            'status' => 'disconnected',
            'config' => null,
        ],
    ];

    public function run(): void
    {
        $organizations = Organization::all();

        foreach ($organizations as $org) {
            foreach (self::INTEGRATIONS as $data) {
                Integration::firstOrCreate(
                    ['organization_id' => $org->id, 'type' => $data['type']],
                    ['status' => $data['status'], 'config' => $data['config']]
                );
            }
        }
    }
}
