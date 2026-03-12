<?php

use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Eod\Steps\AccrueLoanInterest;
use App\Enums\EodStatus;
use App\Enums\LoanStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EodProcess;
use App\Models\EodProcessStep;
use App\Models\LoanAccount;
use App\Models\LoanProduct;
use App\Models\User;
use Carbon\Carbon;

describe('AccrueLoanInterest', function (): void {
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
            'step_number' => AccrueLoanInterest::STEP_NUMBER,
            'step_name' => AccrueLoanInterest::STEP_NAME,
            'status' => EodStatus::Completed,
        ]);

        $step = app(AccrueLoanInterest::class);
        $result = $step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class)
            ->and($this->process->fresh()->completed_steps)->toBe(0);
    });

    it('accrues daily interest for all active loan accounts', function (): void {
        $product = LoanProduct::factory()->create(['code' => 'KMK']);
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
            'interest_rate' => 12.0,
            'accrued_interest' => 0,
        ]);

        $step = app(AccrueLoanInterest::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', AccrueLoanInterest::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(1)
            ->and($this->process->fresh()->completed_steps)->toBe(1);

        $account->refresh();
        expect((float) $account->accrued_interest)->toBeGreaterThan(0);
    });

    it('includes Overdue and Current status loans', function (): void {
        $product = LoanProduct::factory()->create(['code' => 'KMK']);
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
            'status' => LoanStatus::Current,
            'outstanding_principal' => 5000000,
            'interest_rate' => 12.0,
            'accrued_interest' => 0,
        ]);

        LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Overdue,
            'outstanding_principal' => 8000000,
            'interest_rate' => 12.0,
            'accrued_interest' => 100,
        ]);

        $step = app(AccrueLoanInterest::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', AccrueLoanInterest::STEP_NUMBER)->first();
        expect($processStep->records_processed)->toBe(2);
    });

    it('ignores closed loan accounts', function (): void {
        $product = LoanProduct::factory()->create(['code' => 'KMK']);
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
            'status' => LoanStatus::Closed,
            'outstanding_principal' => 0,
            'interest_rate' => 12.0,
            'accrued_interest' => 0,
        ]);

        $step = app(AccrueLoanInterest::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', AccrueLoanInterest::STEP_NUMBER)->first();
        expect($processStep->records_processed)->toBe(0);
    });

    it('calculates correct daily accrual amount', function (): void {
        $product = LoanProduct::factory()->create(['code' => 'KMK']);
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
            'outstanding_principal' => 36500000,
            'interest_rate' => 10.0,
            'accrued_interest' => 0,
        ]);

        $step = app(AccrueLoanInterest::class);
        $step->handle($this->payload, $this->next);

        $account->refresh();

        // dailyRate = 10.0 / 36500, dailyAccrual = 36500000 * dailyRate
        // bcmul truncates to 2 decimal places so result is 9999.99
        $expectedDaily = bcmul('36500000', bcdiv('10.0', '36500', 10), 2);
        expect((float) $account->accrued_interest)->toBe((float) $expectedDaily);
    });
});
