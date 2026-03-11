<?php

use App\Actions\Deposit\EarlyWithdrawDeposit;
use App\Actions\Deposit\PlaceDeposit;
use App\Actions\Deposit\PledgeDeposit;
use App\DTOs\Deposit\PlaceDepositData;
use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Exceptions\Deposit\DepositPledgedException;
use App\Exceptions\Deposit\InvalidDepositStatusException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\User;

describe('EarlyWithdrawDeposit', function (): void {
    beforeEach(function (): void {
        $this->placeDeposit = app(PlaceDeposit::class);
        $this->action = app(EarlyWithdrawDeposit::class);

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

    it('creates penalty and withdrawal transactions and sets status to Withdrawn', function (): void {
        $account = $this->placeDeposit->execute(new PlaceDepositData(
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

        $result = $this->action->execute($account, $this->user);

        expect($result->status)->toBe(DepositStatus::Withdrawn);
        // placement + penalty + withdrawal = 3
        expect($account->transactions()->count())->toBe(3);
    });

    it('throws when deposit is not active', function (): void {
        $account = DepositAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'deposit_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => DepositStatus::Matured,
        ]);

        $this->action->execute($account, $this->user);
    })->throws(InvalidDepositStatusException::class, 'Deposito tidak dalam status aktif');

    it('throws when deposit is pledged', function (): void {
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

        app(PledgeDeposit::class)->execute($account, 'LOAN-001');

        $this->action->execute($account, $this->user);
    })->throws(DepositPledgedException::class, 'Deposito sedang dijaminkan');
});
