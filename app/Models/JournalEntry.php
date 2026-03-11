<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class JournalEntry extends Model implements AuditableContract
{
    use Auditable, HasApproval, HasFactory, SoftDeletes;

    protected $fillable = [
        'journal_number',
        'journal_date',
        'description',
        'source',
        'status',
        'reference_type',
        'reference_id',
        'total_debit',
        'total_credit',
        'branch_id',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'approval_status',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
        'reversal_journal_id',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => JournalSource::class,
            'status' => JournalStatus::class,
            'approval_status' => ApprovalStatus::class,
            'journal_date' => 'date',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'approved_at' => 'datetime',
            'reversed_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<JournalEntryLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function reversalJournal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_journal_id');
    }

    #[Scope]
    protected function posted($query)
    {
        return $query->where('status', JournalStatus::Posted);
    }

    #[Scope]
    protected function bySource($query, JournalSource $source)
    {
        return $query->where('source', $source);
    }

    #[Scope]
    protected function byDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('journal_date', [$startDate, $endDate]);
    }

    public function isBalanced(): bool
    {
        return bccomp((string) $this->total_debit, (string) $this->total_credit, 2) === 0;
    }

    public function isDraft(): bool
    {
        return $this->status === JournalStatus::Draft;
    }

    public function isPosted(): bool
    {
        return $this->status === JournalStatus::Posted;
    }

    public function isReversed(): bool
    {
        return $this->status === JournalStatus::Reversed;
    }

    public function recalculateTotals(): void
    {
        $totals = $this->lines()->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')->first();

        $this->update([
            'total_debit' => $totals->total_debit ?? 0,
            'total_credit' => $totals->total_credit ?? 0,
        ]);
    }
}
