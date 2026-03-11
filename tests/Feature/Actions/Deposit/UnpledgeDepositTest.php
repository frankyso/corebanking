<?php

use App\Actions\Deposit\PlaceDeposit;
use App\Actions\Deposit\PledgeDeposit;
use App\Actions\Deposit\UnpledgeDeposit;
use App\DTOs\Deposit\PlaceDepositData;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Exceptions\Deposit\InvalidDepositStatusException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\User;

describe('UnpledgeDeposit', function (): void {
    beforeEach(function (): void {
        $this->placeDeposit = app(PlaceDeposit::class);
        $this->pledgeAction = app(PledgeDeposit::class);
        $this->action = app(UnpledgeDeposit::class);

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

    it('clears pledge flag and reference', function (): void {
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

        $this->pledgeAction->execute($account, 'LOAN-001');
        $this->action->execute($account);

        $account->refresh();
        expect($account->is_pledged)->toBeFalse()
            ->and($account->pledge_reference)->toBeNull();
    });

    it('throws when deposit is not pledged', function (): void {
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

        $this->action->execute($account);
    })->throws(InvalidDepositStatusException::class, 'Deposito tidak sedang dijaminkan');
});
