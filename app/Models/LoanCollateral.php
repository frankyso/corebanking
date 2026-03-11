<?php

namespace App\Models;

use App\Enums\CollateralType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanCollateral extends Model
{
    protected $fillable = [
        'loan_application_id',
        'loan_account_id',
        'collateral_type',
        'description',
        'document_number',
        'appraised_value',
        'liquidation_value',
        'location',
        'ownership_name',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'collateral_type' => CollateralType::class,
            'appraised_value' => 'decimal:2',
            'liquidation_value' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<LoanApplication, $this>
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    /**
     * @return BelongsTo<LoanAccount, $this>
     */
    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(LoanAccount::class);
    }
}
