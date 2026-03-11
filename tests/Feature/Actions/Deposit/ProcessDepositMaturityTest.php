<?php

use App\Actions\Deposit\PlaceDeposit;
use App\Actions\Deposit\ProcessDepositMaturity;
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
use Carbon\Carbon;

describe('ProcessDepositMaturity', function (): void {
    beforeEach(function (): void {
        $this->placeDeposit = app(PlaceDeposit::class);
        $this->action = app(ProcessDepositMaturity::class);

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

    it('pays interest at maturity and sets status to Matured', function (): void {
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
            placementDate: Carbon::now()->subMonths(12),
        ));

        $matured = $this->action->execute($account, $this->user);

        expect($matured->status)->toBe(DepositStatus::Matured)
            ->and((float) $matured->total_interest_paid)->toBeGreaterThan(0);
    });

    it('triggers rollover when rollover type is not None', function (): void {
        $account = $this->placeDeposit->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 10000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Monthly,
            rolloverType: RolloverType::PrincipalOnly,
            savingsAccountId: null,
            performer: $this->user,
            placementDate: Carbon::now()->subMonths(12),
        ));

        $oldMaturityDate = $account->maturity_date->format('Y-m-d');

        $result = $this->action->execute($account, $this->user);

        expect($result->status)->toBe(DepositStatus::Active)
            ->and($result->placement_date->format('Y-m-d'))->toBe($oldMaturityDate);
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

    it('throws when deposit has not yet matured', function (): void {
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

        $this->action->execute($account, $this->user);
    })->throws(InvalidDepositStatusException::class, 'Deposito belum jatuh tempo');
});
