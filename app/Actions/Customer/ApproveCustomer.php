<?php

namespace App\Actions\Customer;

use App\Enums\CustomerStatus;
use App\Exceptions\Customer\CustomerApprovalException;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveCustomer
{
    public function execute(Customer $customer, User $approver): Customer
    {
        if (! $customer->canBeApprovedBy($approver)) {
            if ($customer->created_by === $approver->id) {
                throw CustomerApprovalException::selfApproval($customer, $approver);
            }

            throw CustomerApprovalException::notPending($customer);
        }

        return DB::transaction(function () use ($customer, $approver): Customer {
            $customer->approve($approver);
            $customer->update(['status' => CustomerStatus::Active]);

            return $customer;
        });
    }
}
