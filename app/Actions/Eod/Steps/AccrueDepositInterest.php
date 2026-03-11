<?php

namespace App\Actions\Eod\Steps;

use App\Actions\Eod\Contracts\EodStep;
use App\Actions\Eod\EodPipelinePayload;
use App\Enums\DepositStatus;
use App\Enums\EodStatus;
use App\Models\DepositAccount;
use App\Models\EodProcessStep;
use App\Services\DepositAccrualService;

class AccrueDepositInterest implements EodStep
{
    public const STEP_NUMBER = 3;

    public const STEP_NAME = 'Deposit Interest Accrual';

    public function __construct(
        private DepositAccrualService $depositAccrualService,
    ) {}

    public function handle(EodPipelinePayload $payload, \Closure $next): EodPipelinePayload
    {
        $step = $this->resolveStep($payload);

        if ($step->status === EodStatus::Completed) {
            return $next($payload);
        }

        try {
            $step->update(['status' => EodStatus::Running, 'started_at' => now()]);

            $accounts = DepositAccount::where('status', DepositStatus::Active)->get();
            $count = 0;

            foreach ($accounts as $account) {
                $this->depositAccrualService->accrueDaily($account, $payload->processDate);
                $count++;
            }

            $step->update([
                'status' => EodStatus::Completed,
                'records_processed' => $count,
                'completed_at' => now(),
            ]);

            $payload->process->increment('completed_steps');

            return $next($payload);
        } catch (\Throwable $e) {
            $step->update([
                'status' => EodStatus::Failed,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $payload->process->update([
                'status' => EodStatus::Failed,
                'error_message' => 'Gagal pada langkah '.self::STEP_NUMBER.': '.$e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function resolveStep(EodPipelinePayload $payload): EodProcessStep
    {
        return $payload->process->steps()->where('step_number', self::STEP_NUMBER)->first()
            ?? EodProcessStep::create([
                'eod_process_id' => $payload->process->id,
                'step_number' => self::STEP_NUMBER,
                'step_name' => self::STEP_NAME,
                'status' => EodStatus::Pending,
            ]);
    }
}
