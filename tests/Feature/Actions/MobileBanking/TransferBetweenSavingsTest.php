<?php

use App\Actions\MobileBanking\TransferBetweenSavings;
use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\MobileBanking\TransferData;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Exceptions\MobileBanking\TransferException;
use App\Exceptions\Savings\InsufficientSavingsBalanceException;
use App\Exceptions\Savings\SavingsBalanceLimitException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileUser;
use App\Models\SavingsProduct;
use App\Models\TransferTransaction;
use App\Models\User;

describe('TransferBetweenSavings', function (): void {
    beforeEach(function (): void {
        $this->action = app(TransferBetweenSavings::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->customer1 = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $this->customer2 = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $this->product = SavingsProduct::factory()->create([
            'code' => 'T01',
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
            'max_balance' => null,
            'closing_fee' => 25000,
        ]);

        $this->sourceAccount = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer1->id,
            branchId: $this->branch->id,
            initialDeposit: 500000,
            performer: $this->user,
        ));

        $this->destinationAccount = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer2->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        $this->mobileUser = MobileUser::factory()->create([
            'customer_id' => $this->customer1->id,
        ]);
    });

    it('successfully transfers between two savings accounts', function (): void {
        $transferAmount = 100000;

        $this->action->execute(new TransferData(
            sourceAccount: $this->sourceAccount,
            destinationAccount: $this->destinationAccount,
            amount: $transferAmount,
            performer: $this->mobileUser,
        ));

        $this->sourceAccount->refresh();
        $this->destinationAccount->refresh();

        expect((float) $this->sourceAccount->balance)->toBe(400000.00)
            ->and((float) $this->destinationAccount->balance)->toBe(200000.00);
    });

    it('creates a TransferTransaction record with correct data', function (): void {
        $transferAmount = 150000;

        $transaction = $this->action->execute(new TransferData(
            sourceAccount: $this->sourceAccount,
            destinationAccount: $this->destinationAccount,
            amount: $transferAmount,
            performer: $this->mobileUser,
            description: 'Test transfer',
        ));

        expect($transaction)->toBeInstanceOf(TransferTransaction::class)
            ->and($transaction->reference_number)->toStartWith('TRF')
            ->and((float) $transaction->amount)->toBe(150000.00)
            ->and((float) $transaction->fee)->toBe(0.00)
            ->and($transaction->description)->toBe('Test transfer')
            ->and($transaction->status)->toBe(TransferStatus::Completed)
            ->and($transaction->source_savings_account_id)->toBe($this->sourceAccount->id)
            ->and($transaction->destination_savings_account_id)->toBe($this->destinationAccount->id)
            ->and($transaction->performed_by)->toBe($this->mobileUser->id)
            ->and($transaction->performed_at)->not->toBeNull();
    });

    it('sets transfer type to OwnAccount when same customer', function (): void {
        $ownAccount = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer1->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        $transaction = $this->action->execute(new TransferData(
            sourceAccount: $this->sourceAccount,
            destinationAccount: $ownAccount,
            amount: 50000,
            performer: $this->mobileUser,
        ));

        expect($transaction->transfer_type)->toBe(TransferType::OwnAccount);
    });

    it('sets transfer type to InternalTransfer when different customers', function (): void {
        $transaction = $this->action->execute(new TransferData(
            sourceAccount: $this->sourceAccount,
            destinationAccount: $this->destinationAccount,
            amount: 50000,
            performer: $this->mobileUser,
        ));

        expect($transaction->transfer_type)->toBe(TransferType::InternalTransfer);
    });

    it('uses default description when none provided', function (): void {
        $transaction = $this->action->execute(new TransferData(
            sourceAccount: $this->sourceAccount,
            destinationAccount: $this->destinationAccount,
            amount: 50000,
            performer: $this->mobileUser,
        ));

        expect($transaction->description)->toBe('Transfer via Mobile Banking');
    });

    it('throws TransferException when source and destination are the same account', function (): void {
        expect(fn () => $this->action->execute(new TransferData(
            sourceAccount: $this->sourceAccount,
            destinationAccount: $this->sourceAccount,
            amount: 50000,
            performer: $this->mobileUser,
        )))->toThrow(TransferException::class, 'Rekening sumber dan tujuan tidak boleh sama.');
    });

    it('throws exception for insufficient balance', function (): void {
        expect(fn () => $this->action->execute(new TransferData(
            sourceAccount: $this->sourceAccount,
            destinationAccount: $this->destinationAccount,
            amount: 999999999,
            performer: $this->mobileUser,
        )))->toThrow(InsufficientSavingsBalanceException::class);
    });

    it('throws exception when remaining balance would be below minimum', function (): void {
        // Source balance = 500000, min_balance = 25000, max withdrawal = 475000
        expect(fn () => $this->action->execute(new TransferData(
            sourceAccount: $this->sourceAccount,
            destinationAccount: $this->destinationAccount,
            amount: 480000,
            performer: $this->mobileUser,
        )))->toThrow(SavingsBalanceLimitException::class);
    });
});
