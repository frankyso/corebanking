<?php

namespace App\Exceptions\Customer;

use App\Exceptions\DomainException;
use App\Models\Customer;
use App\Models\User;

class CustomerApprovalException extends DomainException
{
    public static function selfApproval(Customer $customer, User $user): static
    {
        return (new static('Pembuat data tidak dapat menyetujui/menolak data sendiri'))
            ->withContext([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
            ]);
    }

    public static function notPending(Customer $customer): static
    {
        return (new static('Nasabah tidak dalam status menunggu persetujuan'))
            ->withContext([
                'customer_id' => $customer->id,
                'approval_status' => $customer->approval_status?->value,
            ]);
    }
}
