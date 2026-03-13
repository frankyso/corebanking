<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DepositAccountResource;
use App\Http\Resources\Api\V1\DepositAccountSummaryResource;
use App\Http\Resources\Api\V1\DepositProductResource;
use App\Http\Resources\Api\V1\DepositTransactionResource;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\MobileUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepositAccountController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $accounts = $this->customer($request)
            ->depositAccounts()
            ->with('depositProduct')
            ->get();

        return DepositAccountSummaryResource::collection($accounts);
    }

    public function show(Request $request, string $accountNumber): DepositAccountResource
    {
        $account = $this->findAccount($request, $accountNumber);
        $account->load('depositProduct');

        return DepositAccountResource::make($account);
    }

    public function transactions(Request $request, string $accountNumber): AnonymousResourceCollection
    {
        $account = $this->findAccount($request, $accountNumber);

        $query = $account->transactions()->latest();

        if ($startDate = $request->string('start_date')->value()) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->string('end_date')->value()) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return DepositTransactionResource::collection($query->paginate(20));
    }

    public function products(): AnonymousResourceCollection
    {
        $products = DepositProduct::active()->with('rates')->get();

        return DepositProductResource::collection($products);
    }

    public function interestProjection(Request $request, string $accountNumber): JsonResponse
    {
        $account = $this->findAccount($request, $accountNumber);

        $daysRemaining = $account->maturity_date ? max(0, (int) now()->diffInDays($account->maturity_date, false)) : 0;
        $dailyRate = (float) $account->interest_rate / 365 / 100;
        $projectedInterest = (float) $account->principal_amount * $dailyRate * $daysRemaining;

        return response()->json(['data' => [
            'account_number' => $account->account_number,
            'principal_amount' => (float) $account->principal_amount,
            'interest_rate' => (float) $account->interest_rate,
            'days_remaining' => (int) $daysRemaining,
            'maturity_date' => $account->maturity_date->format('Y-m-d'),
            'accrued_interest' => (float) $account->accrued_interest,
            'projected_interest_to_maturity' => round($projectedInterest, 2),
        ]]);
    }

    private function findAccount(Request $request, string $accountNumber): DepositAccount
    {
        return $this->customer($request)
            ->depositAccounts()
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
