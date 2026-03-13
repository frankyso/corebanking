<?php

namespace Database\Factories;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\SavingsAccount;
use App\Models\TransferTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferTransaction>
 */
class TransferTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_number' => 'TRF'.now()->format('Ymd').fake()->unique()->numerify('######'),
            'source_savings_account_id' => SavingsAccount::factory(),
            'destination_savings_account_id' => SavingsAccount::factory(),
            'amount' => fake()->randomFloat(2, 10000, 5000000),
            'fee' => 0,
            'description' => fake()->sentence(),
            'transfer_type' => TransferType::OwnAccount,
            'status' => TransferStatus::Completed,
            'performed_by' => null,
            'performed_at' => now(),
            'journal_entry_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TransferStatus::Pending,
            'performed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TransferStatus::Failed,
        ]);
    }

    public function internalTransfer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'transfer_type' => TransferType::InternalTransfer,
        ]);
    }
}
