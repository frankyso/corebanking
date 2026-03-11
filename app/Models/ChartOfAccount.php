<?php

namespace App\Models;

use App\Enums\AccountGroup;
use App\Enums\NormalBalance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ChartOfAccount extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'account_code',
        'account_name',
        'account_group',
        'parent_id',
        'level',
        'is_header',
        'is_active',
        'normal_balance',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'account_group' => AccountGroup::class,
            'normal_balance' => NormalBalance::class,
            'is_header' => 'boolean',
            'is_active' => 'boolean',
            'level' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopePostable($query)
    {
        return $query->where('is_header', false)->where('is_active', true);
    }

    public function scopeByGroup($query, AccountGroup $group)
    {
        return $query->where('account_group', $group);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->account_code} - {$this->account_name}";
    }
}
