<?php

use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Eod\Steps\AccrueSavingsInterest;
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

describe('AccrueSavingsInterest', function (): void {
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
            'step_number' => AccrueSavingsInterest::STEP_NUMBER,
            'step_name' => AccrueSavingsInterest::STEP_NAME,
            'status' => EodStatus::Completed,
        ]);

        $step = app(AccrueSavingsInterest::class);
        $result = $step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class)
            ->and($this->process->fresh()->completed_steps)->toBe(0);
    });

    it('accrues interest for all active savings accounts', function (): void {
        $product = SavingsProduct::factory()->create([
            'code' => 'SAV',
            'interest_rate' => 3.5,
        ]);

        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $account = SavingsAccount::create([
            'account_number' => 'SAV001000000001',
            'customer_id' => $customer->id,
            'savings_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 10000000,
            'hold_amount' => 0,
            'opened_at' => now()->subMonths(3),
            'last_transaction_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $step = app(AccrueSavingsInterest::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', AccrueSavingsInterest::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(1)
            ->and($this->process->fresh()->completed_steps)->toBe(1);

        expect($account->interestAccruals()->count())->toBe(1);
    });

    it('processes zero accounts when none are active', function (): void {
        $step = app(AccrueSavingsInterest::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', AccrueSavingsInterest::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(0)
            ->and($this->process->fresh()->completed_steps)->toBe(1);
    });

    it('ignores closed savings accounts', function (): void {
        $product = SavingsProduct::factory()->create([
            'code' => 'SAV',
            'interest_rate' => 3.5,
        ]);

        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        SavingsAccount::create([
            'account_number' => 'SAV001000000002',
            'customer_id' => $customer->id,
            'savings_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Closed,
            'balance' => 0,
            'hold_amount' => 0,
            'opened_at' => now()->subMonths(3),
            'last_transaction_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $step = app(AccrueSavingsInterest::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', AccrueSavingsInterest::STEP_NUMBER)->first();
        expect($processStep->records_processed)->toBe(0);
    });

    it('creates step record if not exists', function (): void {
        expect($this->process->steps()->count())->toBe(0);

        $step = app(AccrueSavingsInterest::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', AccrueSavingsInterest::STEP_NUMBER)->first();
        expect($processStep)->not->toBeNull()
            ->and($processStep->step_name)->toBe('Savings Interest Accrual');
    });
});
