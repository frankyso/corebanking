<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SystemParameter extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_editable',
    ];

    protected function casts(): array
    {
        return [
            'is_editable' => 'boolean',
        ];
    }

    public static function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $param = static::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if (! $param) {
            return $default;
        }

        return match ($param->type) {
            'integer' => (int) $param->value,
            'float', 'decimal' => (float) $param->value,
            'boolean' => filter_var($param->value, FILTER_VALIDATE_BOOLEAN),
            default => $param->value,
        };
    }
}
