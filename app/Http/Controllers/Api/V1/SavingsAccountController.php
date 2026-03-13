<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SavingsAccountResource;
use App\Http\Resources\Api\V1\SavingsAccountSummaryResource;
use App\Http\Resources\Api\V1\SavingsTransactionResource;
use App\Models\Customer;
use App\Models\MobileUser;
use App\Models\SavingsAccount;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavingsAccountController extends Controller
{
    /**
     * List all savings accounts for the authenticated customer.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $accounts = $this->customer($request)
            ->savingsAccounts()
            ->with('savingsProduct')
            ->get();

        return SavingsAccountSummaryResource::collection($accounts);
    }

    /**
     * Show detailed information for a specific savings account.
     */
    public function show(Request $request, string $accountNumber): SavingsAccountResource
    {
        $account = $this->findAccount($request, $accountNumber);
        $account->load(['savingsProduct', 'branch']);

        return SavingsAccountResource::make($account);
    }

    /**
     * Get the current balance for a specific savings account.
     */
    public function balance(Request $request, string $accountNumber): JsonResponse
    {
        $account = $this->findAccount($request, $accountNumber);

        return response()->json([
            'data' => [
                'account_number' => $account->account_number,
                'balance' => (float) $account->balance,
                'hold_amount' => (float) $account->hold_amount,
                'available_balance' => (float) $account->available_balance,
            ],
        ]);
    }

    /**
     * List paginated transactions for a specific savings account.
     *
     * Supports optional query parameters: start_date, end_date (Y-m-d).
     */
    public function transactions(Request $request, string $accountNumber): AnonymousResourceCollection
    {
        $account = $this->findAccount($request, $accountNumber);

        $query = $account->transactions()->latest();

        if ($startDate = $request->query('start_date')) {
            $query->whereDate('created_at', '>=', (string) $startDate);
        }

        if ($endDate = $request->query('end_date')) {
            $query->whereDate('created_at', '<=', (string) $endDate);
        }

        return SavingsTransactionResource::collection($query->paginate(20));
    }

    /**
     * Get the last 10 transactions (mini statement) for a specific savings account.
     */
    public function miniStatement(Request $request, string $accountNumber): AnonymousResourceCollection
    {
        $account = $this->findAccount($request, $accountNumber);

        $transactions = $account->transactions()->latest()->limit(10)->get();

        return SavingsTransactionResource::collection($transactions);
    }

    /**
     * Get all transactions for a specific month.
     *
     * Requires query parameter: month (YYYY-MM format). Defaults to current month.
     */
    public function monthlyStatement(Request $request, string $accountNumber): AnonymousResourceCollection
    {
        $account = $this->findAccount($request, $accountNumber);

        /** @var string $month */
        $month = $request->query('month', now()->format('Y-m'));

        $transactions = $account->transactions()
            ->whereYear('created_at', substr($month, 0, 4))
            ->whereMonth('created_at', substr($month, 5, 2))
            ->oldest()
            ->get();

        return SavingsTransactionResource::collection($transactions);
    }

    /**
     * Find a savings account belonging to the authenticated customer.
     *
     * @throws ModelNotFoundException
     */
    private function findAccount(Request $request, string $accountNumber): SavingsAccount
    {
        return $this->customer($request)
            ->savingsAccounts()
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
