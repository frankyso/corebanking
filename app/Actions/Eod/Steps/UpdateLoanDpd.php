<?php

namespace App\Actions\Eod\Steps;

use App\Actions\Eod\Contracts\EodStep;
use App\Actions\Eod\EodPipelinePayload;
use App\Actions\Loan\UpdateLoanDpd as UpdateLoanDpdAction;
use App\Enums\EodStatus;
use App\Enums\LoanStatus;
use App\Models\EodProcessStep;
use App\Models\LoanAccount;

class UpdateLoanDpd implements EodStep
{
    public const STEP_NUMBER = 7;

    public const STEP_NAME = 'DPD Update';

    public function __construct(
        private UpdateLoanDpdAction $updateLoanDpd,
    ) {}

    public function handle(EodPipelinePayload $payload, \Closure $next): EodPipelinePayload
    {
        $step = $this->resolveStep($payload);

        if ($step->status === EodStatus::Completed) {
            return $next($payload);
        }

        try {
            $step->update(['status' => EodStatus::Running, 'started_at' => now()]);

            $accounts = LoanAccount::whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])->get();
            $count = 0;

            foreach ($accounts as $account) {
                $this->updateLoanDpd->execute($account);
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
