<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LoanAccountResource;
use App\Http\Resources\Api\V1\LoanPaymentResource;
use App\Http\Resources\Api\V1\LoanScheduleResource;
use App\Models\LoanAccount;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoanAccountController extends Controller
{
    /**
     * Show detailed information for a specific loan account.
     */
    public function show(string $accountNumber): LoanAccountResource
    {
        $account = LoanAccount::where('account_number', $accountNumber)
            ->with(['loanProduct', 'schedules'])
            ->firstOrFail();

        return LoanAccountResource::make($account);
    }

    /**
     * Get the amortization schedule for a specific loan account.
     */
    public function schedule(string $accountNumber): AnonymousResourceCollection
    {
        $account = LoanAccount::where('account_number', $accountNumber)->firstOrFail();

        return LoanScheduleResource::collection(
            $account->schedules()->orderBy('installment_number')->get()
        );
    }

    /**
     * List payment history for a specific loan account.
     */
    public function payments(string $accountNumber): AnonymousResourceCollection
    {
        $account = LoanAccount::where('account_number', $accountNumber)->firstOrFail();

        return LoanPaymentResource::collection(
            $account->payments()->latest()->paginate(20)
        );
    }

    /**
     * Get overdue installments for a specific loan account.
     */
    public function overdue(string $accountNumber): AnonymousResourceCollection
    {
        $account = LoanAccount::where('account_number', $accountNumber)->firstOrFail();

        return LoanScheduleResource::collection($account->getOverdueSchedules());
    }
}
