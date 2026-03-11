<?php

use App\Actions\Deposit\PlaceDeposit;
use App\Actions\Deposit\RolloverDeposit;
use App\DTOs\Deposit\PlaceDepositData;
use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\User;
use Carbon\Carbon;

describe('RolloverDeposit', function (): void {
    beforeEach(function (): void {
        $this->placeDeposit = app(PlaceDeposit::class);
        $this->action = app(RolloverDeposit::class);

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

    it('rolls over with PrincipalAndInterest adding net interest to principal', function (): void {
        $account = $this->placeDeposit->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 10000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::PrincipalAndInterest,
            savingsAccountId: null,
            performer: $this->user,
            placementDate: Carbon::now()->subMonths(12),
        ));

        $result = $this->action->execute($account, $this->user);

        expect($result->status)->toBe(DepositStatus::Active)
            ->and((float) $result->principal_amount)->toBeGreaterThan(10000000)
            ->and((float) $result->accrued_interest)->toBe(0.00);
    });

    it('rolls over with PrincipalOnly keeping same principal', function (): void {
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

        $result = $this->action->execute($account, $this->user);

        expect($result->status)->toBe(DepositStatus::Active)
            ->and((float) $result->principal_amount)->toBe(10000000.00);
    });

    it('creates a rollover transaction', function (): void {
        $account = $this->placeDeposit->execute(new PlaceDepositData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            principalAmount: 10000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::PrincipalOnly,
            savingsAccountId: null,
            performer: $this->user,
            placementDate: Carbon::now()->subMonths(12),
        ));

        $this->action->execute($account, $this->user);

        $rolloverTx = $account->transactions()->where('transaction_type', 'rollover')->first();
        expect($rolloverTx)->not->toBeNull()
            ->and($rolloverTx->description)->toBe('Perpanjangan deposito otomatis');
    });
});
