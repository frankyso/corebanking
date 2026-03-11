<?php

use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\User;
use App\Services\DepositService;
use Carbon\Carbon;

describe('DepositService', function (): void {
    beforeEach(function (): void {
        $this->service = app(DepositService::class);

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

    describe('place', function (): void {
        it('creates an active deposit account with correct details', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 10000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

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
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 5000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            expect($account->transactions()->count())->toBe(1);
            $tx = $account->transactions()->first();
            expect($tx->transaction_type)->toBe('placement')
                ->and((float) $tx->amount)->toBe(5000000.00);
        });

        it('pays upfront interest when InterestPaymentMethod is Upfront', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 10000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Upfront,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            // placement + interest_payment + tax = 3 transactions
            expect($account->transactions()->count())->toBe(3);
            expect((float) $account->total_interest_paid)->toBeGreaterThan(0)
                ->and((float) $account->total_tax_paid)->toBeGreaterThan(0);
        });

        it('does not pay upfront interest for maturity payment method', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 10000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            expect($account->transactions()->count())->toBe(1)
                ->and((float) $account->total_interest_paid)->toBe(0.00);
        });

        it('calculates maturity date from placement date plus tenor months', function (): void {
            $placementDate = Carbon::parse('2026-01-15');

            $account = $this->service->place(
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
            );

            expect($account->placement_date->format('Y-m-d'))->toBe('2026-01-15')
                ->and($account->maturity_date->format('Y-m-d'))->toBe('2027-01-15');
        });

        it('throws when principal is below product minimum', function (): void {
            $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 500000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Nominal minimal deposito');

        it('throws when principal exceeds product maximum', function (): void {
            $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 3000000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Nominal maksimal deposito');

        it('throws when no rate exists for the given tenor and amount', function (): void {
            $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 5000000,
                tenorMonths: 6,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Tidak ada suku bunga untuk tenor');
    });

    describe('processMaturity', function (): void {
        it('pays interest at maturity and sets status to Matured', function (): void {
            $account = $this->service->place(
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
            );

            $matured = $this->service->processMaturity($account, $this->user);

            expect($matured->status)->toBe(DepositStatus::Matured)
                ->and((float) $matured->total_interest_paid)->toBeGreaterThan(0);
        });

        it('triggers rollover when rollover type is not None', function (): void {
            $account = $this->service->place(
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
            );

            $oldMaturityDate = $account->maturity_date->format('Y-m-d');

            $result = $this->service->processMaturity($account, $this->user);

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

            $this->service->processMaturity($account, $this->user);
        })->throws(InvalidArgumentException::class, 'Deposito tidak dalam status aktif');

        it('throws when deposit has not yet matured', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 5000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            $this->service->processMaturity($account, $this->user);
        })->throws(InvalidArgumentException::class, 'Deposito belum jatuh tempo');
    });

    describe('rollover', function (): void {
        it('rolls over with PrincipalAndInterest adding net interest to principal', function (): void {
            $account = $this->service->place(
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
            );

            $result = $this->service->rollover($account, $this->user);

            expect($result->status)->toBe(DepositStatus::Active)
                ->and((float) $result->principal_amount)->toBeGreaterThan(10000000)
                ->and((float) $result->accrued_interest)->toBe(0.00);
        });
    });

    describe('earlyWithdrawal', function (): void {
        it('creates penalty and withdrawal transactions and sets status to Withdrawn', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 10000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            $result = $this->service->earlyWithdrawal($account, $this->user);

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

            $this->service->earlyWithdrawal($account, $this->user);
        })->throws(InvalidArgumentException::class, 'Deposito tidak dalam status aktif');

        it('throws when deposit is pledged', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 5000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            $this->service->pledge($account, 'LOAN-001');

            $this->service->earlyWithdrawal($account, $this->user);
        })->throws(InvalidArgumentException::class, 'Deposito sedang dijaminkan');
    });

    describe('pledge', function (): void {
        it('sets is_pledged to true and stores pledge_reference', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 5000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            $this->service->pledge($account, 'LOAN-001');

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

            $this->service->pledge($account, 'LOAN-001');
        })->throws(InvalidArgumentException::class, 'Deposito tidak dalam status aktif');

        it('throws when deposit is already pledged', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 5000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            $this->service->pledge($account, 'LOAN-001');
            $this->service->pledge($account, 'LOAN-002');
        })->throws(InvalidArgumentException::class, 'Deposito sudah dijaminkan');
    });

    describe('unpledge', function (): void {
        it('clears pledge flag and reference', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 5000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            $this->service->pledge($account, 'LOAN-001');
            $this->service->unpledge($account);

            $account->refresh();
            expect($account->is_pledged)->toBeFalse()
                ->and($account->pledge_reference)->toBeNull();
        });

        it('throws when deposit is not pledged', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 5000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            $this->service->unpledge($account);
        })->throws(InvalidArgumentException::class, 'Deposito tidak sedang dijaminkan');
    });

    describe('accrueDaily', function (): void {
        it('creates an interest accrual record with correct amounts', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 10000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Monthly,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            $date = Carbon::parse('2026-03-15');
            $this->service->accrueDaily($account, $date);

            expect($account->interestAccruals()->count())->toBe(1);
            $accrual = $account->interestAccruals()->first();
            expect((float) $accrual->accrued_amount)->toBeGreaterThan(0)
                ->and($accrual->is_posted)->toBeFalse();

            $account->refresh();
            expect((float) $account->accrued_interest)->toBeGreaterThan(0);
        });

        it('does nothing when deposit is not active', function (): void {
            $account = DepositAccount::factory()->create([
                'customer_id' => $this->customer->id,
                'deposit_product_id' => $this->product->id,
                'branch_id' => $this->branch->id,
                'status' => DepositStatus::Matured,
                'accrued_interest' => 0,
            ]);

            $this->service->accrueDaily($account, now());

            expect($account->interestAccruals()->count())->toBe(0);
        });
    });

    describe('payMonthlyInterest', function (): void {
        it('pays accrued interest minus tax for monthly method', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 10000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Monthly,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            // Accrue for several days
            for ($i = 1; $i <= 30; $i++) {
                $this->service->accrueDaily($account, Carbon::parse("2026-03-{$i}"));
                $account->refresh();
            }

            $accruedBefore = (float) $account->accrued_interest;
            expect($accruedBefore)->toBeGreaterThan(0);

            $this->service->payMonthlyInterest($account, $this->user);

            $account->refresh();
            expect((float) $account->accrued_interest)->toBe(0.00)
                ->and((float) $account->total_interest_paid)->toBeGreaterThan(0)
                ->and($account->last_interest_paid_at)->not->toBeNull();

            // Accruals should be marked as posted
            expect($account->interestAccruals()->where('is_posted', false)->count())->toBe(0);
        });

        it('does nothing when payment method is not monthly', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 10000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Maturity,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            $this->service->payMonthlyInterest($account, $this->user);

            expect((float) $account->total_interest_paid)->toBe(0.00);
        });

        it('does nothing when accrued interest is zero', function (): void {
            $account = $this->service->place(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                principalAmount: 10000000,
                tenorMonths: 12,
                interestPaymentMethod: InterestPaymentMethod::Monthly,
                rolloverType: RolloverType::None,
                savingsAccountId: null,
                performer: $this->user,
            );

            $txCountBefore = $account->transactions()->count();
            $this->service->payMonthlyInterest($account, $this->user);

            expect($account->transactions()->count())->toBe($txCountBefore);
        });
    });
});
