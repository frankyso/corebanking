<?php

use App\Actions\Loan\CreateLoanApplication;
use App\DTOs\Loan\CreateLoanApplicationData;
use App\Enums\InterestType;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\Loan\InvalidLoanAmountException;
use App\Exceptions\Loan\InvalidLoanTenorException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\User;

describe('CreateLoanApplication', function (): void {
    beforeEach(function (): void {
        $this->action = app(CreateLoanApplication::class);

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

    it('creates a loan application with Submitted status', function (): void {
        $application = $this->action->execute(new CreateLoanApplicationData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 10000000,
            requestedTenor: 12,
            purpose: 'Modal kerja',
            creator: $this->user,
        ));

        expect($application)->toBeInstanceOf(LoanApplication::class)
            ->and($application->status)->toBe(LoanApplicationStatus::Submitted)
            ->and($application->application_number)->not->toBeNull()
            ->and((float) $application->requested_amount)->toBe(10000000.00)
            ->and($application->requested_tenor_months)->toBe(12)
            ->and((float) $application->interest_rate)->toBe(12.00)
            ->and($application->created_by)->toBe($this->user->id);
    });

    it('associates loan officer when provided', function (): void {
        $officer = User::factory()->create(['branch_id' => $this->branch->id]);

        $application = $this->action->execute(new CreateLoanApplicationData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 5000000,
            requestedTenor: 6,
            purpose: 'Modal kerja',
            creator: $this->user,
            loanOfficerId: $officer->id,
        ));

        expect($application->loan_officer_id)->toBe($officer->id);
    });

    it('throws when requested amount is below product minimum', function (): void {
        $this->action->execute(new CreateLoanApplicationData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 500000,
            requestedTenor: 12,
            purpose: 'Modal kerja',
            creator: $this->user,
        ));
    })->throws(InvalidLoanAmountException::class, 'Jumlah pinjaman kurang dari minimum');

    it('throws when requested amount exceeds product maximum', function (): void {
        $this->action->execute(new CreateLoanApplicationData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 600000000,
            requestedTenor: 12,
            purpose: 'Modal kerja',
            creator: $this->user,
        ));
    })->throws(InvalidLoanAmountException::class, 'Jumlah pinjaman melebihi maksimum');

    it('throws when requested tenor is outside product range', function (): void {
        $this->action->execute(new CreateLoanApplicationData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 5000000,
            requestedTenor: 120,
            purpose: 'Modal kerja',
            creator: $this->user,
        ));
    })->throws(InvalidLoanTenorException::class, 'Tenor harus antara');

    it('allows null max_amount on product', function (): void {
        $product = LoanProduct::factory()->create([
            'code' => 'UNL',
            'max_amount' => null,
            'min_amount' => 1000000,
            'min_tenor_months' => 3,
            'max_tenor_months' => 60,
        ]);

        $application = $this->action->execute(new CreateLoanApplicationData(
            product: $product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            requestedAmount: 999000000,
            requestedTenor: 12,
            purpose: 'Modal kerja',
            creator: $this->user,
        ));

        expect($application->status)->toBe(LoanApplicationStatus::Submitted);
    });
});
