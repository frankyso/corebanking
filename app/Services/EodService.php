<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\EodStatus;
use App\Enums\LoanStatus;
use App\Enums\SavingsAccountStatus;
use App\Enums\TellerSessionStatus;
use App\Models\DepositAccount;
use App\Models\EodProcess;
use App\Models\EodProcessStep;
use App\Models\LoanAccount;
use App\Models\SavingsAccount;
use App\Models\SystemParameter;
use App\Models\TellerSession;
use App\Models\User;
use Carbon\Carbon;

class EodService
{
    private const STEPS = [
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

    public function __construct(
        private SavingsInterestCalculator $savingsInterestCalculator,
        private DepositService $depositService,
        private LoanService $loanService,
    ) {}

    public function run(Carbon $processDate, User $performer): EodProcess
    {
        $existing = EodProcess::whereDate('process_date', $processDate->toDateString())->first();
        if ($existing && $existing->isCompleted()) {
            throw new \InvalidArgumentException("EOD untuk tanggal {$processDate->format('d/m/Y')} sudah pernah dijalankan");
        }

        if ($existing && $existing->isRunning()) {
            throw new \InvalidArgumentException('EOD sedang berjalan');
        }

        $process = $existing ?? EodProcess::create([
            'process_date' => $processDate->toDateString(),
            'status' => EodStatus::Pending,
            'total_steps' => count(self::STEPS),
            'completed_steps' => 0,
            'started_by' => $performer->id,
        ]);

        $process->update([
            'status' => EodStatus::Running,
            'started_at' => now(),
            'error_message' => null,
        ]);

        foreach (self::STEPS as $number => $name) {
            $existingStep = $process->steps()->where('step_number', $number)->first();
            if ($existingStep && $existingStep->status === EodStatus::Completed) {
                continue;
            }

            $step = $existingStep ?? EodProcessStep::create([
                'eod_process_id' => $process->id,
                'step_number' => $number,
                'step_name' => $name,
                'status' => EodStatus::Pending,
            ]);

            try {
                $step->update(['status' => EodStatus::Running, 'started_at' => now()]);

                $recordsProcessed = $this->executeStep($number, $processDate);

                $step->update([
                    'status' => EodStatus::Completed,
                    'records_processed' => $recordsProcessed,
                    'completed_at' => now(),
                ]);

                $process->increment('completed_steps');
            } catch (\Throwable $e) {
                $step->update([
                    'status' => EodStatus::Failed,
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);

                $process->update([
                    'status' => EodStatus::Failed,
                    'error_message' => "Gagal pada langkah {$number}: {$e->getMessage()}",
                ]);

                return $process->fresh();
            }
        }

        $process->update([
            'status' => EodStatus::Completed,
            'completed_at' => now(),
        ]);

        return $process->fresh();
    }

    protected function executeStep(int $stepNumber, Carbon $date): int
    {
        return match ($stepNumber) {
            1 => $this->preCheck($date),
            2 => $this->accrueSavingsInterest($date),
            3 => $this->accrueDepositInterest($date),
            4 => $this->accrueLoanInterest($date),
            5 => $this->postInterest($date),
            6 => $this->processDepositMaturity($date),
            7 => $this->updateDpd(),
            8 => $this->updateCollectibility(),
            9 => $this->calculateCkpn(),
            10 => $this->checkDormancy($date),
            11 => $this->postValidation($date),
        };
    }

    protected function preCheck(Carbon $date): int
    {
        $checks = 0;

        $openSessions = TellerSession::where('status', TellerSessionStatus::Open)->count();
        if ($openSessions > 0) {
            throw new \RuntimeException("Masih ada {$openSessions} sesi teller yang belum ditutup");
        }
        $checks++;

        $previousDate = $date->copy()->subDay();
        $previousEod = EodProcess::where('process_date', $previousDate->toDateString())
            ->where('status', EodStatus::Completed)
            ->exists();

        if ($date->isAfter(Carbon::today()) && ! $previousEod) {
            // Only check previous EOD if it's not the first-ever run
            $anyPreviousEod = EodProcess::where('status', EodStatus::Completed)->exists();
            if ($anyPreviousEod) {
                throw new \RuntimeException("EOD tanggal {$previousDate->format('d/m/Y')} belum dijalankan");
            }
        }
        $checks++;

        return $checks;
    }

    protected function accrueSavingsInterest(Carbon $date): int
    {
        $accounts = SavingsAccount::where('status', SavingsAccountStatus::Active)->get();
        $count = 0;

        foreach ($accounts as $account) {
            $this->savingsInterestCalculator->calculateDailyAccrual($account, $date);
            $count++;
        }

        return $count;
    }

    protected function accrueDepositInterest(Carbon $date): int
    {
        $accounts = DepositAccount::where('status', DepositStatus::Active)->get();
        $count = 0;

        foreach ($accounts as $account) {
            $this->depositService->accrueDaily($account, $date);
            $count++;
        }

        return $count;
    }

    protected function accrueLoanInterest(Carbon $date): int
    {
        $accounts = LoanAccount::whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])->get();
        $count = 0;

        foreach ($accounts as $account) {
            $dailyRate = bcdiv((string) $account->interest_rate, '36500', 10);
            $dailyAccrual = bcmul((string) $account->outstanding_principal, $dailyRate, 2);

            $account->update([
                'accrued_interest' => bcadd((string) $account->accrued_interest, $dailyAccrual, 2),
            ]);
            $count++;
        }

        return $count;
    }

    protected function postInterest(Carbon $date): int
    {
        // Placeholder for GL posting of accrued interest
        // In production, this would create journal entries for interest accrual
        return 0;
    }

    protected function processDepositMaturity(Carbon $date): int
    {
        $maturedDeposits = DepositAccount::where('status', DepositStatus::Active)
            ->where('maturity_date', '<=', $date)
            ->get();

        $count = 0;
        $systemUser = User::where('email', 'admin@corebanking.test')->first();

        foreach ($maturedDeposits as $deposit) {
            if ($systemUser) {
                try {
                    $this->depositService->processMaturity($deposit, $systemUser);
                    $count++;
                } catch (\Throwable) {
                    // Log error but continue processing
                }
            }
        }

        return $count;
    }

    protected function updateDpd(): int
    {
        $accounts = LoanAccount::whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])->get();
        $count = 0;

        foreach ($accounts as $account) {
            $this->loanService->updateDpd($account);
            $count++;
        }

        return $count;
    }

    protected function updateCollectibility(): int
    {
        $accounts = LoanAccount::whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])->get();
        $count = 0;

        foreach ($accounts as $account) {
            $this->loanService->updateCollectibility($account);
            $count++;
        }

        return $count;
    }

    protected function calculateCkpn(): int
    {
        // CKPN already calculated in updateCollectibility
        return LoanAccount::whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])
            ->where('ckpn_amount', '>', 0)
            ->count();
    }

    protected function checkDormancy(Carbon $date): int
    {
        $dormantDays = (int) SystemParameter::getValue('savings', 'dormant_period_days', '365');
        $cutoffDate = $date->copy()->subDays($dormantDays);

        $dormantAccounts = SavingsAccount::where('status', SavingsAccountStatus::Active)
            ->where(function ($query) use ($cutoffDate) {
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

        return $count;
    }

    protected function postValidation(Carbon $date): int
    {
        $validations = 0;

        // Validate all active accounts have valid balances
        $negativeBalances = SavingsAccount::where('status', SavingsAccountStatus::Active)
            ->where('balance', '<', 0)
            ->count();

        if ($negativeBalances > 0) {
            throw new \RuntimeException("Ditemukan {$negativeBalances} rekening tabungan dengan saldo negatif");
        }
        $validations++;

        return $validations;
    }

    public function getStepNames(): array
    {
        return self::STEPS;
    }
}
