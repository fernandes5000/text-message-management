<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Organization> */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'default_number' => '97000',
            'credits' => fake()->numberBetween(1000, 9999),
            'parent_id' => null,
        ];
    }

    public function subAccount(int $parentId): static
    {
        return $this->state(['parent_id' => $parentId]);
    }
}
