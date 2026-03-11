<?php

namespace App\DTOs\Accounting;

use App\Enums\JournalSource;
use App\Models\User;
use Carbon\Carbon;

readonly class CreateJournalData
{
    /**
     * @param  array<int, array{account_id: int, debit: float, credit: float, description?: string}>  $lines
     */
    public function __construct(
        public Carbon $journalDate,
        public string $description,
        public JournalSource $source,
        public array $lines,
        public User $creator,
        public ?int $branchId = null,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public bool $autoPost = false,
    ) {}
}
