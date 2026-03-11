<?php

namespace App\Actions\Eod;

use App\Actions\Eod\Steps\AccrueDepositInterest;
use App\Actions\Eod\Steps\AccrueLoanInterest;
use App\Actions\Eod\Steps\AccrueSavingsInterest;
use App\Actions\Eod\Steps\CalculateCkpn;
use App\Actions\Eod\Steps\CheckDormancy;
use App\Actions\Eod\Steps\PostInterest;
use App\Actions\Eod\Steps\PostValidation;
use App\Actions\Eod\Steps\PreCheckValidation;
use App\Actions\Eod\Steps\ProcessDepositMaturity;
use App\Actions\Eod\Steps\UpdateLoanCollectibility;
use App\Actions\Eod\Steps\UpdateLoanDpd;
use App\DTOs\Eod\EodProcessData;
use App\Enums\EodStatus;
use App\Exceptions\Eod\EodAlreadyRunException;
use App\Models\EodProcess;
use Illuminate\Pipeline\Pipeline;

class RunEodProcess
{
    private const TOTAL_STEPS = 11;

    /** @var array<int, class-string> */
    private const STEPS = [
        PreCheckValidation::class,
        AccrueSavingsInterest::class,
        AccrueDepositInterest::class,
        AccrueLoanInterest::class,
        PostInterest::class,
        ProcessDepositMaturity::class,
        UpdateLoanDpd::class,
        UpdateLoanCollectibility::class,
        CalculateCkpn::class,
        CheckDormancy::class,
        PostValidation::class,
    ];

    /** @var array<int, string> */
    public const STEP_NAMES = [
        1 => 'Pre-check Validation',
        2 => 'Savings Interest Accrual',
        3 => 'Deposit Interest Accrual',
        4 => 'Loan Interest Accrual',
        5 => 'Interest Posting',
        6 => 'Deposit Maturity Processing',
        7 => 'DPD Update',
        8 => 'Collectibility Update',
        9 => 'CKPN Calculation',
        10 => 'Dormancy Check',
        11 => 'Post-validation',
    ];

    public function execute(EodProcessData $dto): EodProcess
    {
        $existing = EodProcess::whereDate('process_date', $dto->processDate->toDateString())->first();

        if ($existing && $existing->isCompleted()) {
            throw EodAlreadyRunException::alreadyCompleted($dto->processDate);
        }

        if ($existing && $existing->isRunning()) {
            throw EodAlreadyRunException::alreadyRunning();
        }

        $process = $existing ?? EodProcess::create([
            'process_date' => $dto->processDate->toDateString(),
            'status' => EodStatus::Pending,
            'total_steps' => self::TOTAL_STEPS,
            'completed_steps' => 0,
            'started_by' => $dto->performer->id,
        ]);

        $process->update([
            'status' => EodStatus::Running,
            'started_at' => now(),
            'error_message' => null,
        ]);

        $payload = new EodPipelinePayload(
            processDate: $dto->processDate,
            performer: $dto->performer,
            process: $process,
        );

        try {
            /** @var EodPipelinePayload $result */
            $result = app(Pipeline::class)
                ->send($payload)
                ->through(self::STEPS)
                ->thenReturn();

            $result->process->update([
                'status' => EodStatus::Completed,
                'completed_at' => now(),
            ]);

            return $result->process->fresh();
        } catch (\Throwable) {
            // Step already updated process status to Failed
            return $process->fresh();
        }
    }

    /**
     * @return array<int, string>
     */
    public function getStepNames(): array
    {
        return self::STEP_NAMES;
    }
}
