<?php

use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Eod\Steps\PostInterest;
use App\Enums\EodStatus;
use App\Models\Branch;
use App\Models\EodProcess;
use App\Models\EodProcessStep;
use App\Models\User;
use Carbon\Carbon;

describe('PostInterest', function (): void {
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

        $this->step = app(PostInterest::class);
    });

    it('skips when step is already completed', function (): void {
        EodProcessStep::create([
            'eod_process_id' => $this->process->id,
            'step_number' => PostInterest::STEP_NUMBER,
            'step_name' => PostInterest::STEP_NAME,
            'status' => EodStatus::Completed,
            'records_processed' => 0,
            'completed_at' => now(),
        ]);

        $result = $this->step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class);

        $this->process->refresh();
        expect($this->process->completed_steps)->toBe(0);
    });

    it('completes with 0 records processed as placeholder step', function (): void {
        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', PostInterest::STEP_NUMBER)->first();
        expect($processStep->status)->toBe(EodStatus::Completed)
            ->and($processStep->records_processed)->toBe(0);
    });

    it('increments process completed_steps', function (): void {
        $this->step->handle($this->payload, $this->next);

        $this->process->refresh();
        expect($this->process->completed_steps)->toBe(1);
    });

    it('creates step record with correct metadata', function (): void {
        $this->step->handle($this->payload, $this->next);

        $processStep = $this->process->steps()->where('step_number', PostInterest::STEP_NUMBER)->first();
        expect($processStep)
            ->step_number->toBe(PostInterest::STEP_NUMBER)
            ->step_name->toBe(PostInterest::STEP_NAME)
            ->completed_at->not->toBeNull();
    });

    it('returns the payload to continue the pipeline', function (): void {
        $result = $this->step->handle($this->payload, $this->next);

        expect($result)->toBeInstanceOf(EodPipelinePayload::class)
            ->and($result->process->id)->toBe($this->process->id);
    });
});
