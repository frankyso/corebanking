<?php

use App\Actions\Deposit\PlaceDeposit;
use App\Actions\Deposit\PledgeDeposit;
use App\DTOs\Deposit\PlaceDepositData;
use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Exceptions\Deposit\InvalidDepositStatusException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\User;

describe('PledgeDeposit', function (): void {
    beforeEach(function (): void {
        $this->placeDeposit = app(PlaceDeposit::class);
        $this->action = app(PledgeDeposit::class);

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

    it('sets is_pledged to true and stores pledge_reference', function (): void {
        $account = $this->placeDeposit->execute(new PlaceDepositData(
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

        $this->action->execute($account, 'LOAN-001');

        $account->refresh();
        expect($account->is_pledged)->toBeTrue()
            ->and($account->pledge_reference)->toBe('LOAN-001');
    });

    it('throws when deposit is not active', function (): void {
        $account = DepositAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'deposit_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => DepositStatus::Matured,
        ]);

        $this->action->execute($account, 'LOAN-001');
    })->throws(InvalidDepositStatusException::class, 'Deposito tidak dalam status aktif');

    it('throws when deposit is already pledged', function (): void {
        $account = $this->placeDeposit->execute(new PlaceDepositData(
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

        $this->action->execute($account, 'LOAN-001');
        $this->action->execute($account, 'LOAN-002');
    })->throws(InvalidDepositStatusException::class, 'Deposito sudah dijaminkan');
});
