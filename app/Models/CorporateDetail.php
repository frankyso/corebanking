<?php

namespace App\Models;

use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporateDetail extends Model
{
    use HasFactory, HasMicrosecondTimestamps;

    protected $fillable = [
        'customer_id',
        'company_name',
        'legal_type',
        'nib',
        'npwp_company',
        'deed_number',
        'deed_date',
        'sk_kemenkumham',
        'business_sector',
        'address_company',
        'city',
        'province',
        'postal_code',
        'phone_office',
        'email',
        'annual_revenue',
        'total_employees',
        'beneficial_owner',
        'authorized_persons',
        'contact_person_name',
        'contact_person_phone',
        'contact_person_position',
    ];

    protected function casts(): array
    {
        return [
            'deed_date' => 'date',
            'annual_revenue' => 'decimal:2',
            'beneficial_owner' => 'array',
            'authorized_persons' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
