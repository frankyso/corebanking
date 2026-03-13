<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerDetailResource;
use App\Http\Resources\Api\V1\CustomerProfileResource;
use App\Http\Resources\Api\V1\DepositAccountSummaryResource;
use App\Http\Resources\Api\V1\LoanAccountSummaryResource;
use App\Http\Resources\Api\V1\SavingsAccountSummaryResource;
use App\Models\Customer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    /**
     * Get customer profile by CIF number.
     */
    public function show(string $cif): CustomerProfileResource
    {
        $customer = Customer::where('cif_number', $cif)->with('branch')->firstOrFail();

        return CustomerProfileResource::make($customer);
    }

    /**
     * Get detailed customer information including individual/corporate details.
     */
    public function detail(string $cif): CustomerDetailResource
    {
        $customer = Customer::where('cif_number', $cif)
            ->with(['branch', 'individualDetail', 'corporateDetail'])
            ->firstOrFail();

        return CustomerDetailResource::make($customer);
    }

    /**
     * List savings accounts for a customer.
     */
    public function savings(string $cif): AnonymousResourceCollection
    {
        $customer = $this->findCustomer($cif);

        return SavingsAccountSummaryResource::collection(
            $customer->savingsAccounts()->with('savingsProduct')->get()
        );
    }

    /**
     * List deposit accounts for a customer.
     */
    public function deposits(string $cif): AnonymousResourceCollection
    {
        $customer = $this->findCustomer($cif);

        return DepositAccountSummaryResource::collection(
            $customer->depositAccounts()->with('depositProduct')->get()
        );
    }

    /**
     * List loan accounts for a customer.
     */
    public function loans(string $cif): AnonymousResourceCollection
    {
        $customer = $this->findCustomer($cif);

        return LoanAccountSummaryResource::collection(
            $customer->loanAccounts()->with('loanProduct')->get()
        );
    }

    private function findCustomer(string $cif): Customer
    {
        return Customer::where('cif_number', $cif)->firstOrFail();
    }
}
