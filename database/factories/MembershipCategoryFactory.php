<?php

namespace Database\Factories;

use App\Models\MembershipCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MembershipCategory> */
class MembershipCategoryFactory extends Factory
{
    /** @return array{name: string, description: string, discount_percent: int} */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'discount_percent' => 0,
        ];
    }
}
