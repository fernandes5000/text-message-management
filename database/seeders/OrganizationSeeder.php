<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // Demo user
        $demo = User::firstOrCreate(
            ['email' => 'demo@textmessage.dev'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'locale' => 'en',
                'theme' => 'light',
                'email_verified_at' => now(),
            ]
        );

        // Main organization
        $main = Organization::firstOrCreate(
            ['name' => 'Acme Corp'],
            [
                'default_number' => '97000',
                'credits' => 3249,
                'parent_id' => null,
            ]
        );

        $main->users()->syncWithoutDetaching([
            $demo->id => ['role' => 'admin'],
        ]);

        $demo->update(['active_organization_id' => $main->id]);

        // Sub-accounts
        $subAccounts = [
            'College Ministry',
            'Easthill Campus',
            'Guest Assimilation',
            "Jordan's Ministry",
            'Main Campus',
            'Pastor Stephen',
        ];

        foreach ($subAccounts as $name) {
            $sub = Organization::firstOrCreate(
                ['name' => $name, 'parent_id' => $main->id],
                [
                    'default_number' => '97000',
                    'credits' => rand(500, 2000),
                ]
            );

            $sub->users()->syncWithoutDetaching([
                $demo->id => ['role' => 'member'],
            ]);
        }
    }
}
