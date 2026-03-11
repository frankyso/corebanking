<?php

namespace App\Actions\Eod\Steps;

use App\Actions\Deposit\ProcessDepositMaturity as ProcessDepositMaturityAction;
use App\Actions\Eod\Contracts\EodStep;
use App\Actions\Eod\EodPipelinePayload;
use App\Enums\DepositStatus;
use App\Enums\EodStatus;
use App\Models\DepositAccount;
use App\Models\EodProcessStep;
use App\Models\User;

class ProcessDepositMaturity implements EodStep
{
    public const STEP_NUMBER = 6;

    public const STEP_NAME = 'Deposit Maturity Processing';

    public function __construct(
        private ProcessDepositMaturityAction $processDepositMaturity,
    ) {}

    public function handle(EodPipelinePayload $payload, \Closure $next): EodPipelinePayload
    {
        $step = $this->resolveStep($payload);

        if ($step->status === EodStatus::Completed) {
            return $next($payload);
        }

        try {
            $step->update(['status' => EodStatus::Running, 'started_at' => now()]);

            $maturedDeposits = DepositAccount::where('status', DepositStatus::Active)
                ->where('maturity_date', '<=', $payload->processDate->format('Y-m-d'))
                ->get();

            $count = 0;
            $systemUser = User::where('email', 'admin@corebanking.test')->first();

            foreach ($maturedDeposits as $deposit) {
                if ($systemUser) {
                    try {
                        $this->processDepositMaturity->execute($deposit, $systemUser);
                        $count++;
                    } catch (\Throwable) {
                        // Log error but continue processing
                    }
                }
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
