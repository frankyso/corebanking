<?php

namespace App\Models;

use App\Enums\AccountGroup;
use App\Enums\NormalBalance;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ChartOfAccount, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    #[Scope]
    protected function postable($query)
    {
        return $query->where('is_header', false)->where('is_active', true);
    }

    #[Scope]
    protected function byGroup($query, AccountGroup $group)
    {
        return $query->where('account_group', $group);
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('is_active', true);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(get: function (): string {
            return "{$this->account_code} - {$this->account_name}";
        });
    }
}
