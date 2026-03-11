<?php

use App\Actions\Loan\UpdateLoanDpd;
use App\Enums\LoanStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\User;

describe('UpdateLoanDpd', function (): void {
    beforeEach(function (): void {
        $this->action = app(UpdateLoanDpd::class);

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

        $this->product = LoanProduct::factory()->create(['code' => 'KMK']);
    });

    it('sets dpd to 0 and status to Current when no overdue schedules', function (): void {
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

        $this->action->execute($account);

        expect($account->fresh()->dpd)->toBe(0)
            ->and($account->fresh()->status)->toBe(LoanStatus::Current);
    });

    it('calculates dpd from oldest overdue schedule', function (): void {
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

        $this->action->execute($account);

        expect($account->fresh()->dpd)->toBe(30)
            ->and($account->fresh()->status)->toBe(LoanStatus::Overdue);
    });
});
