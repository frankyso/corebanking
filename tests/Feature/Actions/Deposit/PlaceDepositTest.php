<?php

use App\Actions\Deposit\PlaceDeposit;
use App\DTOs\Deposit\PlaceDepositData;
use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Exceptions\Deposit\InvalidDepositAmountException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\User;
use Carbon\Carbon;

describe('PlaceDeposit', function (): void {
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

    it('creates an active deposit account with correct details', function (): void {
        $account = $this->action->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 10000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));

        expect($account)->toBeInstanceOf(DepositAccount::class)
            ->and($account->status)->toBe(DepositStatus::Active)
            ->and($account->account_number)->not->toBeNull()
            ->and((float) $account->principal_amount)->toBe(10000000.00)
            ->and((float) $account->interest_rate)->toBe(6.00)
            ->and($account->tenor_months)->toBe(12)
            ->and($account->interest_payment_method)->toBe(InterestPaymentMethod::Maturity)
            ->and($account->rollover_type)->toBe(RolloverType::None)
            ->and($account->is_pledged)->toBeFalse();
    });

    it('creates a placement transaction', function (): void {
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

        expect($account->transactions()->count())->toBe(1);
        $tx = $account->transactions()->first();
        expect($tx->transaction_type)->toBe('placement')
            ->and((float) $tx->amount)->toBe(5000000.00);
    });

    it('pays upfront interest when InterestPaymentMethod is Upfront', function (): void {
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

        // placement + interest_payment + tax = 3 transactions
        expect($account->transactions()->count())->toBe(3);
        expect((float) $account->total_interest_paid)->toBeGreaterThan(0)
            ->and((float) $account->total_tax_paid)->toBeGreaterThan(0);
    });

    it('does not pay upfront interest for maturity payment method', function (): void {
        $account = $this->action->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 10000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));

        expect($account->transactions()->count())->toBe(1)
            ->and((float) $account->total_interest_paid)->toBe(0.00);
    });

    it('calculates maturity date from placement date plus tenor months', function (): void {
        $placementDate = Carbon::parse('2026-01-15');

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
            placementDate: $placementDate,
        ));

        expect($account->placement_date->format('Y-m-d'))->toBe('2026-01-15')
            ->and($account->maturity_date->format('Y-m-d'))->toBe('2027-01-15');
    });

    it('throws when principal is below product minimum', function (): void {
        $this->action->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 500000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));
    })->throws(InvalidDepositAmountException::class, 'Nominal minimal deposito');

    it('throws when principal exceeds product maximum', function (): void {
        $this->action->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 3000000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));
    })->throws(InvalidDepositAmountException::class, 'Nominal maksimal deposito');

    it('throws when no rate exists for the given tenor and amount', function (): void {
        $this->action->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 5000000,
            tenorMonths: 6,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
        ));
    })->throws(InvalidDepositAmountException::class, 'Tidak ada suku bunga untuk tenor');
});
