<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndividualDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'nik',
        'full_name',
        'birth_place',
        'birth_date',
        'gender',
        'marital_status',
        'mother_maiden_name',
        'religion',
        'education',
        'nationality',
        'npwp',
        'address_ktp',
        'address_domicile',
        'rt_rw',
        'kelurahan',
        'kecamatan',
        'city',
        'province',
        'postal_code',
        'phone_mobile',
        'phone_home',
        'email',
        'occupation',
        'employer_name',
        'monthly_income',
        'source_of_fund',
        'transaction_purpose',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'monthly_income' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
