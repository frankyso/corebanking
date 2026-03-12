<?php

use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Eod\Steps\PreCheckValidation;
use App\Enums\EodStatus;
use App\Enums\TellerSessionStatus;
use App\Exceptions\Eod\EodPreCheckFailedException;
use App\Models\Branch;
use App\Models\EodProcess;
use App\Models\EodProcessStep;
use App\Models\TellerSession;
use App\Models\User;
use App\Models\Vault;

describe('PreCheckValidation', function (): void {
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
            processDate: now(),
            performer: $this->user,
        );

        $this->next = fn (EodPipelinePayload $p) => $p;
    });

    it('skips execution when step is already completed', function (): void {
        EodProcessStep::create([
            'eod_process_id' => $this->process->id,
            'step_number' => PreCheckValidation::STEP_NUMBER,
            'step_name' => PreCheckValidation::STEP_NAME,
            'status' => EodStatus::Completed,
        ]);

        $step = app(PreCheckValidation::class);
        $result = $step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class)
            ->and($this->process->fresh()->completed_steps)->toBe(0);
    });

    it('passes when no open teller sessions exist', function (): void {
        $step = app(PreCheckValidation::class);
        $result = $step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class);

        $processStep = $this->process->steps()->where('step_number', PreCheckValidation::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(1)
            ->and($this->process->fresh()->completed_steps)->toBe(1);
    });

    it('throws EodPreCheckFailedException when open teller sessions exist', function (): void {
        $vault = Vault::factory()->create(['branch_id' => $this->branch->id]);

        TellerSession::create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'vault_id' => $vault->id,
            'status' => TellerSessionStatus::Open,
            'opening_balance' => 5000000,
            'current_balance' => 5000000,
            'total_cash_in' => 0,
            'total_cash_out' => 0,
            'transaction_count' => 0,
            'opened_at' => now(),
        ]);

        $step = app(PreCheckValidation::class);
        $step->handle($this->payload, $this->next);
    })->throws(EodPreCheckFailedException::class, 'sesi teller');

    it('marks step and process as Failed on exception', function (): void {
        $vault = Vault::factory()->create(['branch_id' => $this->branch->id]);

        TellerSession::create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'vault_id' => $vault->id,
            'status' => TellerSessionStatus::Open,
            'opening_balance' => 5000000,
            'current_balance' => 5000000,
            'total_cash_in' => 0,
            'total_cash_out' => 0,
            'transaction_count' => 0,
            'opened_at' => now(),
        ]);

        $step = app(PreCheckValidation::class);

        try {
            $step->handle($this->payload, $this->next);
        } catch (EodPreCheckFailedException) {
            // expected
        }

        $processStep = $this->process->steps()->where('step_number', PreCheckValidation::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Failed)
            ->and($processStep->error_message)->toContain('sesi teller')
            ->and($this->process->fresh()->status)->toBe(EodStatus::Failed);
    });

    it('creates step record if not exists', function (): void {
        expect($this->process->steps()->count())->toBe(0);

        $step = app(PreCheckValidation::class);
        $step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', PreCheckValidation::STEP_NUMBER)->first();
        expect($processStep)->not->toBeNull()
            ->and($processStep->step_name)->toBe('Pre-check Validation');
    });
});
