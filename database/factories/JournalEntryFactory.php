<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'journal_number' => 'JRN'.now()->format('Ymd').str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'journal_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'description' => fake()->sentence(),
            'source' => JournalSource::Manual,
            'status' => JournalStatus::Draft,
            'total_debit' => 0,
            'total_credit' => 0,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
            'approval_status' => ApprovalStatus::Pending,
        ];
    }

    public function posted(): static
    {
        return $this->state(fn (): array => [
            'status' => JournalStatus::Posted,
            'approval_status' => ApprovalStatus::Approved,
            'posted_at' => now(),
            'approved_at' => now(),
        ]);
    }
}
