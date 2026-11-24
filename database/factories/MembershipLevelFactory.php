<?php

namespace Database\Factories;

use App\Models\MembershipLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MembershipLevel> */
class MembershipLevelFactory extends Factory
{
    /** @return array{name: string, fee: int, validity_days: int, benefits: string} */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'fee' => 1000,
            'validity_days' => 365,
            'benefits' => "Community access\nLearning resources\nMember updates",
        ];
    }
}
