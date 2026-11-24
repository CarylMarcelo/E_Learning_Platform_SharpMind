<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MembershipTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MembershipTransaction> */
class MembershipTransactionFactory extends Factory
{
    /** @return array{reference_number: string, member_id: Factory, type: string, amount: int, status: string} */
    public function definition(): array
    {
        return [
            'reference_number' => fake()->unique()->bothify('TRX-????-#####'),
            'member_id' => Member::factory(),
            'type' => 'Application',
            'amount' => 1000,
            'status' => 'Pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'Paid']);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'Approved']);
    }
}
