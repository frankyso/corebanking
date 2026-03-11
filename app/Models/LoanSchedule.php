<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanSchedule extends Model
{
    protected $fillable = [
        'loan_account_id',
        'installment_number',
        'due_date',
        'principal_amount',
        'interest_amount',
        'total_amount',
        'outstanding_balance',
        'principal_paid',
        'interest_paid',
        'penalty_paid',
        'is_paid',
        'paid_date',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_date' => 'date',
            'principal_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'principal_paid' => 'decimal:2',
            'interest_paid' => 'decimal:2',
            'penalty_paid' => 'decimal:2',
            'is_paid' => 'boolean',
        ];
    }

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class);
    }

    public function getRemainingPrincipal(): float
    {
        return (float) bcsub((string) $this->principal_amount, (string) $this->principal_paid, 2);
    }

    public function getRemainingInterest(): float
    {
        return (float) bcsub((string) $this->interest_amount, (string) $this->interest_paid, 2);
    }

    public function isOverdue(): bool
    {
        return ! $this->is_paid && $this->due_date->isPast();
    }
}
