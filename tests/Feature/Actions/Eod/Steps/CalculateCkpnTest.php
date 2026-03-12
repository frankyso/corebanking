<?php

use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Eod\Steps\CalculateCkpn;
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

describe('CalculateCkpn', function (): void {
    beforeEach(function (): void {
        $this->branch = Branch::factory()->create();
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
            processDate: Carbon::now(),
            performer: $this->user,
            process: $this->process,
        );

        $this->next = fn (EodPipelinePayload $p) => $p;

        $this->step = app(CalculateCkpn::class);

        $this->product = LoanProduct::factory()->create();
        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
    });

    it('skips when step is already completed', function (): void {
        EodProcessStep::create([
            'eod_process_id' => $this->process->id,
            'step_number' => CalculateCkpn::STEP_NUMBER,
            'step_name' => CalculateCkpn::STEP_NAME,
            'status' => EodStatus::Completed,
            'records_processed' => 3,
            'completed_at' => now(),
        ]);

        $result = $this->step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class);

        $this->process->refresh();
        expect($this->process->completed_steps)->toBe(0);
    });

    it('counts active loans with ckpn_amount greater than zero', function (): void {
        LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'loan_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
            'ckpn_amount' => 500000,
        ]);

        LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'loan_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Overdue,
            'ckpn_amount' => 1200000,
        ]);

        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', CalculateCkpn::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(2);
    });

    it('reports zero when no loans have ckpn_amount', function (): void {
        LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'loan_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
            'ckpn_amount' => 0,
        ]);

        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', CalculateCkpn::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(0);
    });

    it('excludes closed and written off loans from count', function (): void {
        LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'loan_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Closed,
            'ckpn_amount' => 900000,
        ]);

        LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'loan_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::WrittenOff,
            'ckpn_amount' => 5000000,
        ]);

        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', CalculateCkpn::STEP_NUMBER)->first();
        expect($processStep->records_processed)->toBe(0);
    });

    it('increments process completed_steps on success', function (): void {
        $this->step->handle($this->payload, $this->next);

        $this->process->refresh();
        expect($this->process->completed_steps)->toBe(1);

        $processStep = $this->process->steps()->where('step_number', CalculateCkpn::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->completed_at)->not->toBeNull();
    });
});
