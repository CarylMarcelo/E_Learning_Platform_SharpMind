<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Member> */
class MemberFactory extends Factory
{
    /** @return array{membership_number: string, membership_category_id: Factory, membership_level_id: Factory, first_name: string, last_name: string, email: string, phone: string, address: string, status: string} */
    public function definition(): array
    {
        return [
            'membership_number' => fake()->unique()->bothify('MEM-????-#####'),
            'membership_category_id' => MembershipCategory::factory(),
            'membership_level_id' => MembershipLevel::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('09#########'),
            'address' => fake()->address(),
            'status' => 'Pending',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'Active']);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'Inactive']);
    }
}
