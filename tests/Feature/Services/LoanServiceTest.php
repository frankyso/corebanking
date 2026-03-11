<?php

use App\Enums\CollateralType;
use App\Enums\Collectibility;
use App\Enums\InterestType;
use App\Enums\LoanApplicationStatus;
use App\Enums\LoanStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanCollateral;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\User;
use App\Services\LoanService;
use Carbon\Carbon;

describe('LoanService', function () {
    beforeEach(function () {
        $this->service = app(LoanService::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->approver = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $this->product = LoanProduct::factory()->create([
            'code' => 'KMK',
            'interest_type' => InterestType::Annuity,
            'interest_rate' => 12.00,
            'min_amount' => 1000000,
            'max_amount' => 500000000,
            'min_tenor_months' => 3,
            'max_tenor_months' => 60,
        ]);
    });

    describe('createApplication', function () {
        it('creates a loan application with Submitted status', function () {
            $application = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 10000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );

            expect($application)->toBeInstanceOf(LoanApplication::class)
                ->and($application->status)->toBe(LoanApplicationStatus::Submitted)
                ->and($application->application_number)->not->toBeNull()
                ->and((float) $application->requested_amount)->toBe(10000000.00)
                ->and($application->requested_tenor_months)->toBe(12)
                ->and((float) $application->interest_rate)->toBe(12.00)
                ->and($application->created_by)->toBe($this->user->id);
        });

        it('associates loan officer when provided', function () {
            $officer = User::factory()->create(['branch_id' => $this->branch->id]);

            $application = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 5000000,
                requestedTenor: 6,
                purpose: 'Modal kerja',
                creator: $this->user,
                loanOfficerId: $officer->id,
            );

            expect($application->loan_officer_id)->toBe($officer->id);
        });

        it('throws when requested amount is below product minimum', function () {
            $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 500000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Jumlah pinjaman kurang dari minimum');

        it('throws when requested amount exceeds product maximum', function () {
            $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 600000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Jumlah pinjaman melebihi maksimum');

        it('throws when requested tenor is outside product range', function () {
            $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 5000000,
                requestedTenor: 120,
                purpose: 'Modal kerja',
                creator: $this->user,
            );
        })->throws(InvalidArgumentException::class, 'Tenor harus antara');

        it('allows null max_amount on product', function () {
            $product = LoanProduct::factory()->create([
                'code' => 'UNL',
                'max_amount' => null,
                'min_amount' => 1000000,
                'min_tenor_months' => 3,
                'max_tenor_months' => 60,
            ]);

            $application = $this->service->createApplication(
                product: $product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 999000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );

            expect($application->status)->toBe(LoanApplicationStatus::Submitted);
        });
    });

    describe('approveApplication', function () {
        it('approves a submitted application with default amounts', function () {
            $application = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 10000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );

            $approved = $this->service->approveApplication($application, $this->approver);

            expect($approved->status)->toBe(LoanApplicationStatus::Approved)
                ->and((float) $approved->approved_amount)->toBe(10000000.00)
                ->and($approved->approved_tenor_months)->toBe(12)
                ->and($approved->approved_by)->toBe($this->approver->id)
                ->and($approved->approved_at)->not->toBeNull();
        });

        it('approves with custom approved amount and tenor', function () {
            $application = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 10000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );

            $approved = $this->service->approveApplication($application, $this->approver, 8000000, 6);

            expect((float) $approved->approved_amount)->toBe(8000000.00)
                ->and($approved->approved_tenor_months)->toBe(6);
        });

        it('throws when application is not in approvable status', function () {
            $application = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 10000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );

            $this->service->approveApplication($application, $this->approver);

            $this->service->approveApplication($application->fresh(), $this->approver);
        })->throws(InvalidArgumentException::class, 'Permohonan tidak dalam status yang dapat disetujui');

        it('throws when approver is the same as creator', function () {
            $application = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 10000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );

            $this->service->approveApplication($application, $this->user);
        })->throws(InvalidArgumentException::class, 'Tidak dapat menyetujui permohonan yang Anda buat sendiri');
    });

    describe('rejectApplication', function () {
        it('rejects a submitted application with reason', function () {
            $application = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 10000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );

            $rejected = $this->service->rejectApplication($application, $this->approver, 'Tidak memenuhi syarat');

            expect($rejected->status)->toBe(LoanApplicationStatus::Rejected)
                ->and($rejected->rejection_reason)->toBe('Tidak memenuhi syarat');
        });

        it('throws when application is not in rejectable status', function () {
            $application = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 10000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );

            $this->service->approveApplication($application, $this->approver);

            $this->service->rejectApplication($application->fresh(), $this->approver, 'Alasan');
        })->throws(InvalidArgumentException::class, 'Permohonan tidak dalam status yang dapat ditolak');
    });

    describe('disburse', function () {
        beforeEach(function () {
            $this->application = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 10000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );
            $this->service->approveApplication($this->application, $this->approver);
            $this->application->refresh();
        });

        it('creates a loan account from an approved application', function () {
            $account = $this->service->disburse($this->application, $this->user);

            expect($account)->toBeInstanceOf(LoanAccount::class)
                ->and($account->account_number)->not->toBeNull()
                ->and($account->status)->toBe(LoanStatus::Active)
                ->and((float) $account->principal_amount)->toBe(10000000.00)
                ->and((float) $account->outstanding_principal)->toBe(10000000.00)
                ->and((float) $account->interest_rate)->toBe(12.00)
                ->and($account->tenor_months)->toBe(12)
                ->and($account->customer_id)->toBe($this->customer->id);
        });

        it('generates amortization schedule with correct number of installments', function () {
            $account = $this->service->disburse($this->application, $this->user);

            expect($account->schedules()->count())->toBe(12);

            $firstSchedule = $account->schedules()->orderBy('installment_number')->first();
            expect($firstSchedule->installment_number)->toBe(1)
                ->and((float) $firstSchedule->total_amount)->toBeGreaterThan(0);
        });

        it('updates application status to Disbursed', function () {
            $this->service->disburse($this->application, $this->user);

            expect($this->application->fresh()->status)->toBe(LoanApplicationStatus::Disbursed);
        });

        it('calculates maturity date correctly', function () {
            $disbDate = Carbon::parse('2026-01-15');
            $account = $this->service->disburse($this->application, $this->user, $disbDate);

            expect($account->disbursement_date->format('Y-m-d'))->toBe('2026-01-15')
                ->and($account->maturity_date->format('Y-m-d'))->toBe('2027-01-15');
        });

        it('copies collaterals from application to account', function () {
            LoanCollateral::create([
                'loan_application_id' => $this->application->id,
                'collateral_type' => CollateralType::Land,
                'description' => 'Tanah 100m2',
                'appraised_value' => 200000000,
                'liquidation_value' => 150000000,
            ]);

            $account = $this->service->disburse($this->application, $this->user);

            expect($account->collaterals()->count())->toBe(1);
        });

        it('throws when application is not approved', function () {
            $newApp = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 5000000,
                requestedTenor: 6,
                purpose: 'Test',
                creator: $this->user,
            );

            $this->service->disburse($newApp, $this->user);
        })->throws(InvalidArgumentException::class, 'Permohonan belum disetujui');
    });

    describe('makePayment', function () {
        beforeEach(function () {
            $this->application = $this->service->createApplication(
                product: $this->product,
                customerId: $this->customer->id,
                branchId: $this->branch->id,
                requestedAmount: 12000000,
                requestedTenor: 12,
                purpose: 'Modal kerja',
                creator: $this->user,
            );
            $this->service->approveApplication($this->application, $this->approver);
            $this->application->refresh();

            $this->loanAccount = $this->service->disburse(
                $this->application,
                $this->user,
                Carbon::parse('2025-01-15'),
            );
        });

        it('creates a LoanPayment record with correct portions', function () {
            $firstSchedule = $this->loanAccount->schedules()->orderBy('installment_number')->first();
            $paymentAmount = (float) $firstSchedule->total_amount;

            $payment = $this->service->makePayment($this->loanAccount, $paymentAmount, $this->user);

            expect($payment->loan_account_id)->toBe($this->loanAccount->id)
                ->and($payment->reference_number)->toStartWith('PAY')
                ->and((float) $payment->amount)->toBe($paymentAmount)
                ->and((float) $payment->interest_portion)->toBeGreaterThan(0)
                ->and((float) $payment->principal_portion)->toBeGreaterThan(0);
        });

        it('updates loan account outstanding principal', function () {
            $firstSchedule = $this->loanAccount->schedules()->orderBy('installment_number')->first();
            $originalPrincipal = (float) $this->loanAccount->outstanding_principal;

            $payment = $this->service->makePayment($this->loanAccount, (float) $firstSchedule->total_amount, $this->user);

            $this->loanAccount->refresh();
            expect((float) $this->loanAccount->outstanding_principal)->toBeLessThan($originalPrincipal)
                ->and((float) $this->loanAccount->total_principal_paid)->toBeGreaterThan(0)
                ->and((float) $this->loanAccount->total_interest_paid)->toBeGreaterThan(0);
        });

        it('marks schedule as paid when fully covered', function () {
            $firstSchedule = $this->loanAccount->schedules()->orderBy('installment_number')->first();

            $this->service->makePayment($this->loanAccount, (float) $firstSchedule->total_amount, $this->user);

            $firstSchedule->refresh();
            expect($firstSchedule->is_paid)->toBeTrue()
                ->and($firstSchedule->paid_date)->not->toBeNull();
        });

        it('handles partial payment that covers only interest', function () {
            $firstSchedule = $this->loanAccount->schedules()->orderBy('installment_number')->first();
            $interestOnly = (float) $firstSchedule->interest_amount;

            $payment = $this->service->makePayment($this->loanAccount, $interestOnly, $this->user);

            expect((float) $payment->interest_portion)->toBe($interestOnly)
                ->and((float) $payment->principal_portion)->toBe(0.00);

            $firstSchedule->refresh();
            expect($firstSchedule->is_paid)->toBeFalse();
        });

        it('closes loan when outstanding principal reaches zero', function () {
            $schedules = $this->loanAccount->schedules()->orderBy('installment_number')->get();
            $totalDue = $schedules->sum(fn ($s) => (float) $s->total_amount);

            $this->service->makePayment($this->loanAccount, $totalDue, $this->user);

            expect($this->loanAccount->fresh()->status)->toBe(LoanStatus::Closed);
        });

        it('throws when loan is not in active status', function () {
            $this->loanAccount->update(['status' => LoanStatus::Closed]);

            $this->service->makePayment($this->loanAccount, 1000000, $this->user);
        })->throws(InvalidArgumentException::class, 'Pinjaman tidak dalam status aktif');

        it('throws when payment amount is zero or negative', function () {
            $this->service->makePayment($this->loanAccount, 0, $this->user);
        })->throws(InvalidArgumentException::class, 'Jumlah pembayaran harus lebih dari 0');
    });

    describe('updateDpd', function () {
        it('sets dpd to 0 and status to Current when no overdue schedules', function () {
            $account = LoanAccount::factory()->create([
                'customer_id' => $this->customer->id,
                'loan_product_id' => $this->product->id,
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'status' => LoanStatus::Active,
                'dpd' => 0,
            ]);

            LoanSchedule::factory()->create([
                'loan_account_id' => $account->id,
                'due_date' => now()->addMonth(),
                'is_paid' => false,
            ]);

            $this->service->updateDpd($account);

            expect($account->fresh()->dpd)->toBe(0)
                ->and($account->fresh()->status)->toBe(LoanStatus::Current);
        });

        it('calculates dpd from oldest overdue schedule', function () {
            $account = LoanAccount::factory()->create([
                'customer_id' => $this->customer->id,
                'loan_product_id' => $this->product->id,
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'status' => LoanStatus::Active,
                'dpd' => 0,
            ]);

            LoanSchedule::factory()->create([
                'loan_account_id' => $account->id,
                'due_date' => now()->subDays(30),
                'is_paid' => false,
            ]);

            $this->service->updateDpd($account);

            expect($account->fresh()->dpd)->toBe(30)
                ->and($account->fresh()->status)->toBe(LoanStatus::Overdue);
        });
    });

    describe('updateCollectibility', function () {
        beforeEach(function () {
            $this->loanAccount = LoanAccount::factory()->create([
                'customer_id' => $this->customer->id,
                'loan_product_id' => $this->product->id,
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'status' => LoanStatus::Active,
                'outstanding_principal' => 10000000,
            ]);
        });

        it('sets Current collectibility for dpd 0', function () {
            $this->loanAccount->update(['dpd' => 0]);
            $this->service->updateCollectibility($this->loanAccount);

            expect($this->loanAccount->fresh()->collectibility)->toBe(Collectibility::Current);
        });

        it('sets SpecialMention for dpd 1-90', function () {
            $this->loanAccount->update(['dpd' => 45]);
            $this->service->updateCollectibility($this->loanAccount);

            expect($this->loanAccount->fresh()->collectibility)->toBe(Collectibility::SpecialMention);
        });

        it('sets Substandard for dpd 91-120', function () {
            $this->loanAccount->update(['dpd' => 100]);
            $this->service->updateCollectibility($this->loanAccount);

            expect($this->loanAccount->fresh()->collectibility)->toBe(Collectibility::Substandard);
        });

        it('sets Doubtful for dpd 121-180', function () {
            $this->loanAccount->update(['dpd' => 150]);
            $this->service->updateCollectibility($this->loanAccount);

            expect($this->loanAccount->fresh()->collectibility)->toBe(Collectibility::Doubtful);
        });

        it('sets Loss for dpd above 180', function () {
            $this->loanAccount->update(['dpd' => 200]);
            $this->service->updateCollectibility($this->loanAccount);

            $account = $this->loanAccount->fresh();
            expect($account->collectibility)->toBe(Collectibility::Loss)
                ->and((float) $account->ckpn_amount)->toBeGreaterThan(0);
        });

        it('calculates CKPN amount based on collectibility rate', function () {
            $this->loanAccount->update(['dpd' => 200, 'outstanding_principal' => 10000000]);
            $this->service->updateCollectibility($this->loanAccount);

            $account = $this->loanAccount->fresh();
            // Loss = 100% CKPN
            expect((float) $account->ckpn_amount)->toBe(10000000.00);
        });
    });
});
