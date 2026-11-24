<?php

namespace Database\Factories;

use App\Models\MembershipTransaction;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    /** @return array{membership_transaction_id: Factory, payment_reference: string, amount_paid: int, payment_method: string, payment_date: Carbon} */
    public function definition(): array
    {
        return [
            'membership_transaction_id' => MembershipTransaction::factory()->paid(),
            'payment_reference' => fake()->unique()->bothify('PAY-????-#####'),
            'amount_paid' => 1000,
            'payment_method' => 'GCash',
            'payment_date' => now(),
        ];
    }
}
