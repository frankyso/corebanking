<?php

namespace App\DTOs\Customer;

use App\Models\User;

readonly class CreateCustomerData
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public User $creator,
    ) {}
}
