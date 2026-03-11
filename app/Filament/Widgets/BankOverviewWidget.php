<?php

namespace App\Filament\Widgets;

use App\Models\DepositAccount;
use App\Models\LoanAccount;
use App\Models\SavingsAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class BankOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalSavings = SavingsAccount::query()->active()->sum('balance');
        $totalDeposits = DepositAccount::query()->active()->sum('principal_amount');
        $totalOutstanding = LoanAccount::query()->active()->sum('outstanding_principal');
        $totalAssets = $totalOutstanding;

        $activeLoanCount = LoanAccount::query()->active()->count();
        $activeSavingsCount = SavingsAccount::query()->active()->count();
        $activeDepositCount = DepositAccount::query()->active()->count();

        return [
            Stat::make('Outstanding Kredit', 'Rp '.Number::abbreviate($totalAssets, 1))
                ->description($activeLoanCount.' rekening aktif')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            Stat::make('Total Tabungan', 'Rp '.Number::abbreviate($totalSavings, 1))
                ->description($activeSavingsCount.' rekening aktif')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('success'),
            Stat::make('Total Deposito', 'Rp '.Number::abbreviate($totalDeposits, 1))
                ->description($activeDepositCount.' bilyet aktif')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('info'),
            Stat::make('Baki Debet', 'Rp '.Number::abbreviate($totalOutstanding, 1))
                ->description('Sisa pokok yang belum lunas')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning'),
        ];
    }
}
