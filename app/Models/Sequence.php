<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
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
