<?php

use App\Actions\Deposit\PlaceDeposit;
use App\DTOs\Deposit\PlaceDepositData;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\User;

describe('CreatesDepositTransaction concern', function (): void {
    beforeEach(function (): void {
        $this->action = app(PlaceDeposit::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $this->product = DepositProduct::factory()->create([
            'code' => 'DEP',
            'min_amount' => 1000000,
            'max_amount' => 2000000000,
            'penalty_rate' => 0.5,
            'tax_rate' => 20,
        ]);

        $this->rate = DepositProductRate::create([
            'deposit_product_id' => $this->product->id,
            'tenor_months' => 12,
            'min_amount' => 1000000,
            'max_amount' => null,
            'interest_rate' => 6.0,
            'is_active' => true,
        ]);
    });

    it('creates a transaction with correct fields', function (): void {
        $account = $this->action->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 5000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));

        $transaction = $account->transactions()->first();

        expect($transaction)->not->toBeNull()
            ->and($transaction->transaction_type)->toBe('placement')
            ->and((float) $transaction->amount)->toBe(5000000.00)
            ->and($transaction->deposit_account_id)->toBe($account->id)
            ->and($transaction->performed_by)->toBe($this->user->id)
            ->and($transaction->transaction_date)->not->toBeNull()
            ->and($transaction->description)->toBe('Penempatan deposito');
    });

    it('generates reference number starting with DEP prefix', function (): void {
        $account = $this->action->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 5000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));

        $transaction = $account->transactions()->first();

        expect($transaction->reference_number)->toStartWith('DEP')
            ->and(strlen($transaction->reference_number))->toBe(17); // DEP + 8 date digits + 6 random digits
    });

    it('generates unique reference numbers for multiple transactions', function (): void {
        $account = $this->action->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 10000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Upfront,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));

        $references = $account->transactions()->pluck('reference_number')->toArray();

        // Upfront creates placement + interest_payment + tax = 3 transactions
        expect(count($references))->toBe(3)
            ->and(count(array_unique($references)))->toBe(3);
    });

    it('calculates total interest correctly', function (): void {
        $account = $this->action->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 10000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Upfront,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));

        // 10,000,000 * 6% * 12/12 = 600,000 gross interest
        // tax = 600,000 * 20% = 120,000
        // net interest = 600,000 - 120,000 = 480,000
        expect((float) $account->total_interest_paid)->toBe(480000.00)
            ->and((float) $account->total_tax_paid)->toBe(120000.00);
    });

    it('creates tax transaction when tax rate is positive', function (): void {
        $account = $this->action->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 10000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Upfront,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));

        $taxTransaction = $account->transactions()->where('transaction_type', 'tax')->first();

        expect($taxTransaction)->not->toBeNull()
            ->and((float) $taxTransaction->amount)->toBe(120000.00)
            ->and($taxTransaction->reference_number)->toStartWith('DEP');
    });
});
