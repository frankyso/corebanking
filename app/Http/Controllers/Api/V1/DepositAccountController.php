<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DepositAccountResource;
use App\Http\Resources\Api\V1\DepositTransactionResource;
use App\Models\DepositAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepositAccountController extends Controller
{
    /**
     * Show detailed information for a specific deposit account.
     */
    public function show(string $accountNumber): DepositAccountResource
    {
        $account = DepositAccount::where('account_number', $accountNumber)
            ->with('depositProduct')
            ->firstOrFail();

        return DepositAccountResource::make($account);
    }

    /**
     * List paginated transactions for a specific deposit account.
     *
     * Supports optional query parameters: start_date, end_date (Y-m-d).
     */
    public function transactions(Request $request, string $accountNumber): AnonymousResourceCollection
    {
        $account = DepositAccount::where('account_number', $accountNumber)->firstOrFail();

        $query = $account->transactions()->latest();

        if ($startDate = $request->string('start_date')->value()) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->string('end_date')->value()) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return DepositTransactionResource::collection($query->paginate(20));
    }
}
