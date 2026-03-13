<?php

namespace App\Models;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $reference_number
 * @property string $amount
 * @property string $fee
 * @property TransferType $transfer_type
 * @property TransferStatus $status
 * @property Carbon|null $performed_at
 */
class TransferTransaction extends Model
{
    use HasFactory, HasMicrosecondTimestamps;

    protected $fillable = [
        'reference_number',
        'source_savings_account_id',
        'destination_savings_account_id',
        'amount',
        'fee',
        'description',
        'transfer_type',
        'status',
        'performed_by',
        'performed_at',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'transfer_type' => TransferType::class,
            'status' => TransferStatus::class,
            'performed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SavingsAccount, $this>
     */
    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class, 'source_savings_account_id');
    }

    /**
     * @return BelongsTo<SavingsAccount, $this>
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class, 'destination_savings_account_id');
    }

    /**
     * @return BelongsTo<MobileUser, $this>
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(MobileUser::class, 'performed_by');
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
