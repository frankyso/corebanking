<?php

use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Eod\Steps\UpdateLoanDpd;
use App\Enums\EodStatus;
use App\Enums\LoanStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EodProcess;
use App\Models\EodProcessStep;
use App\Models\LoanAccount;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\User;
use Carbon\Carbon;

describe('UpdateLoanDpd Step', function (): void {
    beforeEach(function (): void {
        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->process = EodProcess::create([
            'process_date' => now()->toDateString(),
            'status' => EodStatus::Running,
            'total_steps' => 11,
            'completed_steps' => 0,
            'started_at' => now(),
            'started_by' => $this->user->id,
        ]);

        $this->payload = new EodPipelinePayload(
            process: $this->process,
            processDate: Carbon::parse('2026-03-10'),
            performer: $this->user,
        );

        $this->next = fn (EodPipelinePayload $p) => $p;
    });

    it('skips execution when step is already completed', function (): void {
        EodProcessStep::create([
            'eod_process_id' => $this->process->id,
            'step_number' => UpdateLoanDpd::STEP_NUMBER,
            'step_name' => UpdateLoanDpd::STEP_NAME,
            'status' => EodStatus::Completed,
        ]);

        $step = app(UpdateLoanDpd::class);
        $result = $step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class)
            ->and($this->process->fresh()->completed_steps)->toBe(0);
    });

    it('updates DPD for active loan accounts with overdue schedules', function (): void {
        $product = LoanProduct::factory()->create(['code' => 'KRD']);
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $account = LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
            'outstanding_principal' => 10000000,
            'dpd' => 0,
        ]);

        LoanSchedule::factory()->create([
            'loan_account_id' => $account->id,
            'due_date' => now()->subDays(30),
            'is_paid' => false,
        ]);

        $step = app(UpdateLoanDpd::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', UpdateLoanDpd::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(1)
            ->and($this->process->fresh()->completed_steps)->toBe(1);

        $account->refresh();
        expect($account->dpd)->toBeGreaterThan(0)
            ->and($account->status)->toBe(LoanStatus::Overdue);
    });

    it('sets DPD to 0 for accounts with no overdue schedules', function (): void {
        $product = LoanProduct::factory()->create(['code' => 'KRD']);
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $account = LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
            'outstanding_principal' => 10000000,
            'dpd' => 5,
        ]);

        LoanSchedule::factory()->create([
            'loan_account_id' => $account->id,
            'due_date' => now()->addMonth(),
            'is_paid' => false,
        ]);

        $step = app(UpdateLoanDpd::class);
        $step->handle($this->payload, $this->next);

        $account->refresh();
        expect($account->dpd)->toBe(0)
            ->and($account->status)->toBe(LoanStatus::Current);
    });

    it('processes multiple loan accounts across statuses', function (): void {
        $product = LoanProduct::factory()->create(['code' => 'KRD']);
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
            'outstanding_principal' => 10000000,
            'dpd' => 0,
        ]);

        LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Overdue,
            'outstanding_principal' => 5000000,
            'dpd' => 30,
        ]);

        $step = app(UpdateLoanDpd::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', UpdateLoanDpd::STEP_NUMBER)->first();
        expect($processStep->records_processed)->toBe(2);
    });

    it('creates step record if not exists', function (): void {
        expect($this->process->steps()->count())->toBe(0);

        $step = app(UpdateLoanDpd::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', UpdateLoanDpd::STEP_NUMBER)->first();
        expect($processStep)->not->toBeNull()
            ->and($processStep->step_name)->toBe('DPD Update');
    });
});
