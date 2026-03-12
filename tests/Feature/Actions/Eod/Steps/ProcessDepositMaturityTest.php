<?php

use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Eod\Steps\ProcessDepositMaturity;
use App\Enums\DepositStatus;
use App\Enums\EodStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\EodProcess;
use App\Models\EodProcessStep;
use App\Models\User;
use Carbon\Carbon;

describe('ProcessDepositMaturity', function (): void {
    beforeEach(function (): void {
        $this->branch = Branch::factory()->create();
        $this->user = User::factory()->create([
            'email' => 'admin@corebanking.test',
            'branch_id' => $this->branch->id,
        ]);

        $this->process = EodProcess::create([
            'process_date' => now()->toDateString(),
            'status' => EodStatus::Running,
            'total_steps' => 11,
            'completed_steps' => 0,
            'started_at' => now(),
            'started_by' => $this->user->id,
        ]);

        $this->payload = new EodPipelinePayload(
            processDate: Carbon::now(),
            performer: $this->user,
            process: $this->process,
        );

        $this->next = fn (EodPipelinePayload $p) => $p;

        $this->step = app(ProcessDepositMaturity::class);

        $this->product = DepositProduct::factory()->create();
        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
    });

    it('skips when step is already completed', function (): void {
        EodProcessStep::create([
            'eod_process_id' => $this->process->id,
            'step_number' => ProcessDepositMaturity::STEP_NUMBER,
            'step_name' => ProcessDepositMaturity::STEP_NAME,
            'status' => EodStatus::Completed,
            'records_processed' => 2,
            'completed_at' => now(),
        ]);

        $result = $this->step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class);

        $this->process->refresh();
        expect($this->process->completed_steps)->toBe(0);
    });

    it('processes matured deposits via ProcessDepositMaturity action', function (): void {
        DepositAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'deposit_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => DepositStatus::Active,
            'principal_amount' => 10000000,
            'interest_rate' => 6.0,
            'tenor_months' => 3,
            'interest_payment_method' => InterestPaymentMethod::Monthly,
            'rollover_type' => RolloverType::None,
            'placement_date' => now()->subMonths(3),
            'maturity_date' => now()->subDay(),
            'created_by' => $this->user->id,
        ]);

        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', ProcessDepositMaturity::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(1);
    });

    it('handles no matured deposits gracefully', function (): void {
        DepositAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'deposit_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => DepositStatus::Active,
            'principal_amount' => 10000000,
            'interest_rate' => 6.0,
            'tenor_months' => 12,
            'placement_date' => now()->subMonth(),
            'maturity_date' => now()->addMonths(11),
            'created_by' => $this->user->id,
        ]);

        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', ProcessDepositMaturity::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(0);
    });

    it('marks step as completed and increments process completed_steps', function (): void {
        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', ProcessDepositMaturity::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->completed_at)->not->toBeNull();

        $this->process->refresh();
        expect($this->process->completed_steps)->toBe(1);
    });

    it('skips deposits that are not active', function (): void {
        DepositAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'deposit_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => DepositStatus::Matured,
            'principal_amount' => 10000000,
            'interest_rate' => 6.0,
            'tenor_months' => 3,
            'placement_date' => now()->subMonths(3),
            'maturity_date' => now()->subDay(),
            'created_by' => $this->user->id,
        ]);

        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', ProcessDepositMaturity::STEP_NUMBER)->first();
        expect($processStep->records_processed)->toBe(0);
    });
});
