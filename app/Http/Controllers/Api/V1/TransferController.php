<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\MobileBanking\SendMobileNotification;
use App\Actions\MobileBanking\TransferBetweenSavings;
use App\DTOs\MobileBanking\TransferData;
use App\Enums\NotificationType;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transfer\TransferRequest;
use App\Http\Requests\Api\V1\Transfer\ValidateTransferRequest;
use App\Http\Resources\Api\V1\TransferTransactionResource;
use App\Models\Customer;
use App\Models\MobileUser;
use App\Models\SavingsAccount;
use App\Models\TransferTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransferController extends Controller
{
    /**
     * Validate a destination account before initiating a transfer.
     */
    public function validateTransfer(ValidateTransferRequest $request): JsonResponse
    {
        $account = SavingsAccount::where('account_number', $request->input('destination_account_number'))->first();

        if (! $account) {
            return response()->json(['message' => 'Rekening tujuan tidak ditemukan.'], 404);
        }

        $account->load('customer');

        return response()->json([
            'data' => [
                'account_number' => $account->account_number,
                'account_name' => $account->customer?->display_name,
            ],
        ]);
    }

    /**
     * Transfer between own accounts (same customer).
     */
    public function ownAccount(TransferRequest $request): JsonResponse
    {
        $customer = $this->customer($request);
        $source = $customer->savingsAccounts()->where('account_number', $request->input('source_account_number'))->firstOrFail();
        $destination = SavingsAccount::where('account_number', $request->input('destination_account_number'))->firstOrFail();

        if ($destination->customer_id !== $customer->id) {
            return response()->json(['message' => 'Rekening tujuan bukan milik Anda.'], 422);
        }

        return $this->executeTransfer($request, $source, $destination);
    }

    /**
     * Transfer to another customer's account (internal bank transfer).
     */
    public function internal(TransferRequest $request): JsonResponse
    {
        $customer = $this->customer($request);
        $source = $customer->savingsAccounts()->where('account_number', $request->input('source_account_number'))->firstOrFail();
        $destination = SavingsAccount::where('account_number', $request->input('destination_account_number'))->firstOrFail();

        return $this->executeTransfer($request, $source, $destination);
    }

    /**
     * List the authenticated user's transfer history.
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        $accountIds = $this->customer($request)->savingsAccounts()->pluck('id');

        $transfers = TransferTransaction::query()
            ->where(fn ($q) => $q->whereIn('source_savings_account_id', $accountIds)->orWhereIn('destination_savings_account_id', $accountIds))
            ->with(['sourceAccount', 'destinationAccount'])
            ->latest('performed_at')
            ->paginate(20);

        return TransferTransactionResource::collection($transfers);
    }

    /**
     * Show a specific transfer transaction by reference number.
     */
    public function show(Request $request, string $referenceNumber): TransferTransactionResource
    {
        $accountIds = $this->customer($request)->savingsAccounts()->pluck('id');

        $transfer = TransferTransaction::where('reference_number', $referenceNumber)
            ->where(fn ($q) => $q->whereIn('source_savings_account_id', $accountIds)->orWhereIn('destination_savings_account_id', $accountIds))
            ->with(['sourceAccount', 'destinationAccount'])
            ->firstOrFail();

        return TransferTransactionResource::make($transfer);
    }

    /**
     * Execute the transfer using the TransferBetweenSavings action.
     */
    private function executeTransfer(TransferRequest $request, SavingsAccount $source, SavingsAccount $destination): JsonResponse
    {
        $mobileUser = $this->mobileUser($request);
        $description = $request->string('description')->value();
        $dto = new TransferData(
            sourceAccount: $source,
            destinationAccount: $destination,
            amount: $request->float('amount'),
            performer: $mobileUser,
            description: $description !== '' ? $description : null,
        );

        try {
            $transfer = app(TransferBetweenSavings::class)->execute($dto);

            app(SendMobileNotification::class)->execute(
                $mobileUser,
                'Transfer Berhasil',
                'Transfer sebesar Rp '.number_format($dto->amount, 0, ',', '.').' berhasil.',
                NotificationType::Transaction,
            );

            return response()->json([
                'data' => TransferTransactionResource::make($transfer),
                'message' => 'Transfer berhasil.',
            ]);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
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
