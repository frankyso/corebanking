<?php

use App\Actions\Loan\ApproveLoanApplication;
use App\Actions\Loan\CreateLoanApplication;
use App\DTOs\Loan\ApproveLoanApplicationData;
use App\DTOs\Loan\CreateLoanApplicationData;
use App\Enums\InterestType;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\Loan\InvalidLoanStatusException;
use App\Exceptions\Loan\LoanSelfApprovalException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanProduct;
use App\Models\User;

describe('ApproveLoanApplication', function (): void {
    beforeEach(function (): void {
        $this->action = app(ApproveLoanApplication::class);

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

    it('approves a submitted application with default amounts', function (): void {
        $approved = $this->action->execute(new ApproveLoanApplicationData(
            application: $this->application,
            approver: $this->approver,
        ));

        expect($approved->status)->toBe(LoanApplicationStatus::Approved)
            ->and((float) $approved->approved_amount)->toBe(10000000.00)
            ->and($approved->approved_tenor_months)->toBe(12)
            ->and($approved->approved_by)->toBe($this->approver->id)
            ->and($approved->approved_at)->not->toBeNull();
    });

    it('approves with custom approved amount and tenor', function (): void {
        $approved = $this->action->execute(new ApproveLoanApplicationData(
            application: $this->application,
            approver: $this->approver,
            approvedAmount: 8000000,
            approvedTenor: 6,
        ));

        expect((float) $approved->approved_amount)->toBe(8000000.00)
            ->and($approved->approved_tenor_months)->toBe(6);
    });

    it('throws when application is not in approvable status', function (): void {
        $this->action->execute(new ApproveLoanApplicationData(
            application: $this->application,
            approver: $this->approver,
        ));

        $this->action->execute(new ApproveLoanApplicationData(
            application: $this->application->fresh(),
            approver: $this->approver,
        ));
    })->throws(InvalidLoanStatusException::class, 'Permohonan tidak dalam status yang dapat disetujui');

    it('throws when approver is the same as creator', function (): void {
        $this->action->execute(new ApproveLoanApplicationData(
            application: $this->application,
            approver: $this->user,
        ));
    })->throws(LoanSelfApprovalException::class, 'Tidak dapat menyetujui permohonan yang Anda buat sendiri');
});
