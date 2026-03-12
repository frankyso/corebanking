<?php

use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Eod\Steps\CheckDormancy;
use App\Enums\EodStatus;
use App\Enums\SavingsAccountStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EodProcess;
use App\Models\EodProcessStep;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\SystemParameter;
use App\Models\User;
use Carbon\Carbon;

describe('CheckDormancy', function (): void {
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

        $this->step = app(CheckDormancy::class);

        SystemParameter::create([
            'group' => 'savings',
            'key' => 'dormant_period_days',
            'value' => '180',
            'type' => 'integer',
        ]);

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
            'step_number' => CheckDormancy::STEP_NUMBER,
            'step_name' => CheckDormancy::STEP_NAME,
            'status' => EodStatus::Completed,
            'records_processed' => 5,
            'completed_at' => now(),
        ]);

        $result = $this->step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class);

        $this->process->refresh();
        expect($this->process->completed_steps)->toBe(0);
    });

    it('marks dormant accounts with last_transaction_at older than dormant_period_days', function (): void {
        SavingsAccount::create([
            'account_number' => 'DRM001000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 500000,
            'hold_amount' => 0,
            'opened_at' => now()->subYear(),
            'last_transaction_at' => now()->subDays(200),
            'created_by' => $this->user->id,
        ]);

        SavingsAccount::create([
            'account_number' => 'DRM001000000002',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 300000,
            'hold_amount' => 0,
            'opened_at' => now()->subYear(),
            'last_transaction_at' => now()->subDays(250),
            'created_by' => $this->user->id,
        ]);

        $this->step->handle($this->payload, $this->next);

        expect(SavingsAccount::where('account_number', 'DRM001000000001')->first()->status)
            ->toBe(SavingsAccountStatus::Dormant)
            ->and(SavingsAccount::where('account_number', 'DRM001000000002')->first()->status)
            ->toBe(SavingsAccountStatus::Dormant);
    });

    it('skips accounts with null last_transaction_at', function (): void {
        SavingsAccount::create([
            'account_number' => 'DRM002000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 100000,
            'hold_amount' => 0,
            'opened_at' => now()->subYear(),
            'last_transaction_at' => null,
            'created_by' => $this->user->id,
        ]);

        $this->step->handle($this->payload, $this->next);

        $account = SavingsAccount::where('account_number', 'DRM002000000001')->first();
        expect($account->status)->toBe(SavingsAccountStatus::Active);

        $processStep = $this->process->steps()->where('step_number', CheckDormancy::STEP_NUMBER)->first();
        expect($processStep->records_processed)->toBe(0);
    });

    it('does not mark accounts with recent transactions as dormant', function (): void {
        SavingsAccount::create([
            'account_number' => 'DRM003000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 200000,
            'hold_amount' => 0,
            'opened_at' => now()->subMonths(3),
            'last_transaction_at' => now()->subDays(10),
            'created_by' => $this->user->id,
        ]);

        $this->step->handle($this->payload, $this->next);

        $account = SavingsAccount::where('account_number', 'DRM003000000001')->first();
        expect($account->status)->toBe(SavingsAccountStatus::Active);
    });

    it('marks step as completed with correct count and increments process completed_steps', function (): void {
        SavingsAccount::create([
            'account_number' => 'DRM004000000001',
            'customer_id' => $this->customer->id,
            'savings_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 500000,
            'hold_amount' => 0,
            'opened_at' => now()->subYear(),
            'last_transaction_at' => now()->subDays(200),
            'created_by' => $this->user->id,
        ]);

        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', CheckDormancy::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(1)
            ->and($processStep->completed_at)->not->toBeNull();

        $this->process->refresh();
        expect($this->process->completed_steps)->toBe(1);
    });
});
