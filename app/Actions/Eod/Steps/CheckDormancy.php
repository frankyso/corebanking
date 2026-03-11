<?php

namespace App\Actions\Eod\Steps;

use App\Actions\Eod\Contracts\EodStep;
use App\Actions\Eod\EodPipelinePayload;
use App\Enums\EodStatus;
use App\Enums\SavingsAccountStatus;
use App\Models\EodProcessStep;
use App\Models\SavingsAccount;
use App\Models\SystemParameter;

class CheckDormancy implements EodStep
{
    public const STEP_NUMBER = 10;

    public const STEP_NAME = 'Dormancy Check';

    public function handle(EodPipelinePayload $payload, \Closure $next): EodPipelinePayload
    {
        $step = $this->resolveStep($payload);

        if ($step->status === EodStatus::Completed) {
            return $next($payload);
        }

        try {
            $step->update(['status' => EodStatus::Running, 'started_at' => now()]);

            $dormantDays = (int) SystemParameter::getValue('savings', 'dormant_period_days', '365');
            $cutoffDate = $payload->processDate->copy()->subDays($dormantDays);

            $dormantAccounts = SavingsAccount::where('status', SavingsAccountStatus::Active)
                ->where(function ($query) use ($cutoffDate): void {
                    $query->where('last_transaction_at', '<', $cutoffDate)
                        ->orWhereNull('last_transaction_at');
                })
                ->get();

            $count = 0;
            foreach ($dormantAccounts as $account) {
                if ($account->last_transaction_at && $account->last_transaction_at < $cutoffDate) {
                    $account->update(['status' => SavingsAccountStatus::Dormant]);
                    $count++;
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
