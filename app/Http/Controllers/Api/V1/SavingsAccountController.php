<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SavingsAccountResource;
use App\Http\Resources\Api\V1\SavingsTransactionResource;
use App\Models\SavingsAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavingsAccountController extends Controller
{
    /**
     * Show detailed information for a specific savings account.
     */
    public function show(string $accountNumber): SavingsAccountResource
    {
        $account = SavingsAccount::where('account_number', $accountNumber)
            ->with(['savingsProduct', 'branch'])
            ->firstOrFail();

        return SavingsAccountResource::make($account);
    }

    /**
     * Get the current balance for a specific savings account.
     */
    public function balance(string $accountNumber): JsonResponse
    {
        $account = SavingsAccount::where('account_number', $accountNumber)->firstOrFail();

        return response()->json(['data' => [
            'account_number' => $account->account_number,
            'balance' => (float) $account->balance,
            'hold_amount' => (float) $account->hold_amount,
            'available_balance' => (float) $account->available_balance,
        ]]);
    }

    /**
     * List paginated transactions for a specific savings account.
     *
     * Supports optional query parameters: start_date, end_date (Y-m-d).
     */
    public function transactions(Request $request, string $accountNumber): AnonymousResourceCollection
    {
        $account = SavingsAccount::where('account_number', $accountNumber)->firstOrFail();

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
     * Get all transactions for a specific month.
     *
     * Requires query parameter: month (YYYY-MM format). Defaults to current month.
     */
    public function statement(Request $request, string $accountNumber): AnonymousResourceCollection
    {
        $account = SavingsAccount::where('account_number', $accountNumber)->firstOrFail();

        /** @var string $month */
        $month = $request->query('month', now()->format('Y-m'));

        $transactions = $account->transactions()
            ->whereYear('created_at', substr($month, 0, 4))
            ->whereMonth('created_at', substr($month, 5, 2))
            ->oldest()
            ->get();

        return SavingsTransactionResource::collection($transactions);
    }
}
