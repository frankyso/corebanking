<?php

use App\Actions\Loan\ApproveLoanApplication;
use App\Actions\Loan\CreateLoanApplication;
use App\Actions\Loan\RejectLoanApplication;
use App\DTOs\Loan\ApproveLoanApplicationData;
use App\DTOs\Loan\CreateLoanApplicationData;
use App\Enums\InterestType;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\Loan\InvalidLoanStatusException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanProduct;
use App\Models\User;

describe('RejectLoanApplication', function (): void {
    beforeEach(function (): void {
        $this->action = app(RejectLoanApplication::class);

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

        $this->application = app(CreateLoanApplication::class)->execute(new CreateLoanApplicationData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 10000000,
            requestedTenor: 12,
            purpose: 'Modal kerja',
            creator: $this->user,
        ));
    });

    it('rejects a submitted application with reason', function (): void {
        $rejected = $this->action->execute($this->application, $this->approver, 'Tidak memenuhi syarat');

        expect($rejected->status)->toBe(LoanApplicationStatus::Rejected)
            ->and($rejected->rejection_reason)->toBe('Tidak memenuhi syarat');
    });

    it('throws when application is not in rejectable status', function (): void {
        app(ApproveLoanApplication::class)->execute(new ApproveLoanApplicationData(
            application: $this->application,
            approver: $this->approver,
        ));

        $this->action->execute($this->application->fresh(), $this->approver, 'Alasan');
    })->throws(InvalidLoanStatusException::class, 'Permohonan tidak dalam status yang dapat ditolak');
});
