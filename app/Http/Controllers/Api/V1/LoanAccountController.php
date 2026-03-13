<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\MobileBanking\PayLoanFromSavings;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Loan\PayFromSavingsRequest;
use App\Http\Resources\Api\V1\LoanAccountResource;
use App\Http\Resources\Api\V1\LoanAccountSummaryResource;
use App\Http\Resources\Api\V1\LoanPaymentResource;
use App\Http\Resources\Api\V1\LoanScheduleResource;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\MobileUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoanAccountController extends Controller
{
    /**
     * List all loan accounts for the authenticated customer.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $accounts = $this->customer($request)
            ->loanAccounts()
            ->with('loanProduct')
            ->get();

        return LoanAccountSummaryResource::collection($accounts);
    }

    /**
     * Show detailed information for a specific loan account.
     */
    public function show(Request $request, string $accountNumber): LoanAccountResource
    {
        $account = $this->findAccount($request, $accountNumber);
        $account->load(['loanProduct', 'schedules']);

        return LoanAccountResource::make($account);
    }

    /**
     * Get the amortization schedule for a specific loan account.
     */
    public function schedule(Request $request, string $accountNumber): AnonymousResourceCollection
    {
        $account = $this->findAccount($request, $accountNumber);

        return LoanScheduleResource::collection($account->schedules()->orderBy('installment_number')->get());
    }

    /**
     * Get the next unpaid installment for a specific loan account.
     */
    public function nextInstallment(Request $request, string $accountNumber): JsonResponse
    {
        $account = $this->findAccount($request, $accountNumber);

        $schedule = $account->getNextUnpaidSchedule();

        if (! $schedule) {
            return response()->json(['message' => 'Tidak ada angsuran yang belum dibayar.'], 404);
        }

        return LoanScheduleResource::make($schedule)->response();
    }

    /**
     * List payment history for a specific loan account.
     */
    public function payments(Request $request, string $accountNumber): AnonymousResourceCollection
    {
        $account = $this->findAccount($request, $accountNumber);

        return LoanPaymentResource::collection($account->payments()->latest()->paginate(20));
    }

    /**
     * Get overdue installments for a specific loan account.
     */
    public function overdue(Request $request, string $accountNumber): AnonymousResourceCollection
    {
        $account = $this->findAccount($request, $accountNumber);

        return LoanScheduleResource::collection($account->getOverdueSchedules());
    }

    /**
     * Pay a loan installment from a savings account.
     */
    public function payFromSavings(PayFromSavingsRequest $request, string $accountNumber): JsonResponse
    {
        $mobileUser = $this->mobileUser($request);
        $customer = $this->customer($request);
        $loanAccount = $customer->loanAccounts()->where('account_number', $accountNumber)->firstOrFail();
        $savingsAccount = $customer->savingsAccounts()->where('account_number', $request->input('savings_account_number'))->firstOrFail();

        try {
            $payment = app(PayLoanFromSavings::class)->execute(
                $savingsAccount,
                $loanAccount,
                $request->float('amount'),
                $mobileUser,
            );

            return response()->json([
                'data' => LoanPaymentResource::make($payment),
                'message' => 'Pembayaran berhasil.',
            ]);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Find a loan account belonging to the authenticated customer.
     *
     * @throws ModelNotFoundException
     */
    private function findAccount(Request $request, string $accountNumber): LoanAccount
    {
        return $this->customer($request)
            ->loanAccounts()
            ->where('account_number', $accountNumber)
            ->firstOrFail();
    }

    private function mobileUser(Request $request): MobileUser
    {
        /** @var MobileUser */
        return $request->user('mobile');
    }

    private function customer(Request $request): Customer
    {
        /** @var Customer */
        return $this->mobileUser($request)->customer;
    }
}
