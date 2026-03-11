<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    use HasMicrosecondTimestamps;

    protected $fillable = [
        'type',
        'prefix',
        'last_number',
        'padding',
    ];

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
            'padding' => 'integer',
        ];
    }
}
