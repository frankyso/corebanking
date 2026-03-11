<?php

namespace Database\Factories;

use App\Enums\SavingsAccountStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsAccount>
 */
class SavingsAccountFactory extends Factory
{
    public function definition(): array
    {
        $balance = fake()->randomFloat(2, 100000, 50000000);

        return [
            'account_number' => fake()->unique()->numerify('T##001#########'),
            'customer_id' => Customer::factory(),
            'savings_product_id' => SavingsProduct::factory(),
            'branch_id' => Branch::query()->inRandomOrder()->value('id') ?? 1,
            'status' => SavingsAccountStatus::Active,
            'balance' => $balance,
            'hold_amount' => 0,
            'available_balance' => $balance,
            'accrued_interest' => 0,
            'opened_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'last_transaction_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'created_by' => User::query()->inRandomOrder()->value('id') ?? 1,
        ];
    }
}
