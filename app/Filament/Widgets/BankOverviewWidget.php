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

        return [
            Stat::make('Total Outstanding Kredit', 'Rp '.Number::abbreviate($totalAssets, 1))
                ->description('Aset produktif')
                ->icon('heroicon-o-banknotes')
                ->color('primary'),
            Stat::make('Total Tabungan', 'Rp '.Number::abbreviate($totalSavings, 1))
                ->description('Dana pihak ketiga')
                ->icon('heroicon-o-wallet')
                ->color('success'),
            Stat::make('Total Deposito', 'Rp '.Number::abbreviate($totalDeposits, 1))
                ->description('Dana pihak ketiga')
                ->icon('heroicon-o-lock-closed')
                ->color('info'),
            Stat::make('Outstanding Kredit', 'Rp '.Number::abbreviate($totalOutstanding, 1))
                ->description('Baki debet')
                ->icon('heroicon-o-credit-card')
                ->color('warning'),
        ];
    }
}
