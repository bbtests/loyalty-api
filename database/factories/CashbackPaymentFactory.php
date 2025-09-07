<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CashbackPayment>
 */
class CashbackPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'transaction_id' => Transaction::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'payment_provider' => $this->faker->randomElement(['Paystack', 'Flutterwave', 'Stripe']),
            'provider_transaction_id' => $this->faker->uuid(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'payment_details' => json_encode(['reference' => $this->faker->uuid()]),
        ];
    }
}
