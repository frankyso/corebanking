<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDocument extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'document_number',
        'file_path',
        'file_name',
        'expiry_date',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'is_verified' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
