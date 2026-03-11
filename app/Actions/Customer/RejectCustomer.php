<?php

namespace App\Actions\Customer;

use App\Exceptions\Customer\CustomerApprovalException;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RejectCustomer
{
    public function execute(Customer $customer, User $approver, string $reason): Customer
    {
        if (! $customer->canBeApprovedBy($approver)) {
            if ($customer->created_by === $approver->id) {
                throw CustomerApprovalException::selfApproval($customer, $approver);
            }

            throw CustomerApprovalException::notPending($customer);
        }

        return DB::transaction(function () use ($customer, $approver, $reason): Customer {
            $customer->reject($approver, $reason);

            return $customer;
        });
    }
}
