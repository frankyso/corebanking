<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $mobile_user_id
 * @property int $savings_account_id
 * @property string $alias
 */
class TransferFavorite extends Model
{
    use HasFactory, HasMicrosecondTimestamps;

    protected $fillable = [
        'mobile_user_id',
        'savings_account_id',
        'alias',
    ];

    /**
     * @return BelongsTo<MobileUser, $this>
     */
    public function mobileUser(): BelongsTo
    {
        return $this->belongsTo(MobileUser::class);
    }

    /**
     * @return BelongsTo<SavingsAccount, $this>
     */
    public function savingsAccount(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class);
    }
}
