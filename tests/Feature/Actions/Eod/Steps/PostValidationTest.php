<?php

use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Eod\Steps\PostValidation;
use App\Enums\EodStatus;
use App\Enums\SavingsAccountStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EodProcess;
use App\Models\EodProcessStep;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\User;
use Carbon\Carbon;

describe('PostValidation', function (): void {
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

        $this->step = app(PostValidation::class);

        $this->product = SavingsProduct::factory()->create();
        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
    });

    it('skips when step is already completed', function (): void {
        EodProcessStep::create([
            'eod_process_id' => $this->process->id,
            'step_number' => PostValidation::STEP_NUMBER,
            'step_name' => PostValidation::STEP_NAME,
            'status' => EodStatus::Completed,
            'records_processed' => 1,
            'completed_at' => now(),
        ]);

        $result = $this->step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class);

        $this->process->refresh();
        expect($this->process->completed_steps)->toBe(0);
    });

    it('passes validation when no active accounts have negative balances', function (): void {
        SavingsAccount::create([
            'account_number' => 'PV001000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 500000,
            'hold_amount' => 0,
            'opened_at' => now()->subMonth(),
            'last_transaction_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', PostValidation::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(1);

        $this->process->refresh();
        expect($this->process->completed_steps)->toBe(1);
    });

    it('throws RuntimeException when negative balance accounts exist', function (): void {
        SavingsAccount::create([
            'account_number' => 'PV002000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => -50000,
            'hold_amount' => 0,
            'opened_at' => now()->subMonth(),
            'last_transaction_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $this->step->handle($this->payload, $this->next);
    })->throws(RuntimeException::class, 'saldo negatif');

    it('marks step and process as failed when negative balances detected', function (): void {
        SavingsAccount::create([
            'account_number' => 'PV003000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => -100000,
            'hold_amount' => 0,
            'opened_at' => now()->subMonth(),
            'last_transaction_at' => now(),
            'created_by' => $this->user->id,
        ]);

        try {
            $this->step->handle($this->payload, $this->next);
        } catch (RuntimeException) {
            // Expected
        }

        $processStep = $this->process->steps()->where('step_number', PostValidation::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Failed)
            ->and($processStep->error_message)->toContain('saldo negatif');

        $this->process->refresh();
        expect($this->process->status)->toBe(EodStatus::Failed)
            ->and($this->process->error_message)->toContain('saldo negatif');
    });

    it('does not flag closed accounts with negative balances', function (): void {
        SavingsAccount::create([
            'account_number' => 'PV004000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Closed,
            'balance' => -50000,
            'hold_amount' => 0,
            'opened_at' => now()->subYear(),
            'closed_at' => now()->subMonth(),
            'last_transaction_at' => now()->subMonth(),
            'created_by' => $this->user->id,
        ]);

        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', PostValidation::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed);
    });
});
