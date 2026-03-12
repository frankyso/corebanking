<?php

use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Eod\Steps\UpdateLoanCollectibility;
use App\Enums\Collectibility;
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

describe('UpdateLoanCollectibility Step', function (): void {
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
            'step_number' => UpdateLoanCollectibility::STEP_NUMBER,
            'step_name' => UpdateLoanCollectibility::STEP_NAME,
            'status' => EodStatus::Completed,
        ]);

        $step = app(UpdateLoanCollectibility::class);
        $result = $step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class)
            ->and($this->process->fresh()->completed_steps)->toBe(0);
    });

    it('updates collectibility for active loan accounts', function (): void {
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
            'dpd' => 100,
            'collectibility' => Collectibility::Current,
        ]);

        $step = app(UpdateLoanCollectibility::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', UpdateLoanCollectibility::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(1)
            ->and($this->process->fresh()->completed_steps)->toBe(1);

        $account->refresh();
        expect($account->collectibility)->toBe(Collectibility::Substandard)
            ->and((float) $account->ckpn_amount)->toBeGreaterThan(0);
    });

    it('processes overdue accounts alongside active and current', function (): void {
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
            'dpd' => 200,
        ]);

        LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Current,
            'outstanding_principal' => 8000000,
            'dpd' => 0,
        ]);

        $step = app(UpdateLoanCollectibility::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', UpdateLoanCollectibility::STEP_NUMBER)->first();
        expect($processStep->records_processed)->toBe(3);
    });

    it('ignores closed loan accounts', function (): void {
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
            'status' => LoanStatus::Closed,
            'outstanding_principal' => 0,
            'dpd' => 0,
        ]);

        $step = app(UpdateLoanCollectibility::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', UpdateLoanCollectibility::STEP_NUMBER)->first();
        expect($processStep->records_processed)->toBe(0);
    });

    it('creates step record if not exists', function (): void {
        expect($this->process->steps()->count())->toBe(0);

        $step = app(UpdateLoanCollectibility::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', UpdateLoanCollectibility::STEP_NUMBER)->first();
        expect($processStep)->not->toBeNull()
            ->and($processStep->step_name)->toBe('Collectibility Update');
    });
});
