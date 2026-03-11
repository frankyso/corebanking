<?php

namespace App\Filament\Widgets;

use App\Enums\Collectibility;
use App\Models\LoanAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NplRatioWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalOutstanding = (float) LoanAccount::query()->active()->sum('outstanding_principal');
        $nplOutstanding = (float) LoanAccount::query()
            ->active()
            ->whereIn('collectibility', [Collectibility::Substandard, Collectibility::Doubtful, Collectibility::Loss])
            ->sum('outstanding_principal');
        $totalCkpn = (float) LoanAccount::query()->active()->sum('ckpn_amount');

        $nplRatio = $totalOutstanding > 0 ? ($nplOutstanding / $totalOutstanding) * 100 : 0;
        $ckpnCoverage = $nplOutstanding > 0 ? ($totalCkpn / $nplOutstanding) * 100 : 0;

        $nplColor = match (true) {
            $nplRatio <= 5 => 'success',
            $nplRatio <= 8 => 'warning',
            default => 'danger',
        };

        return [
            Stat::make('NPL Ratio', number_format($nplRatio, 2).'%')
                ->description($nplRatio <= 5 ? 'Sehat (maks 5%)' : 'Perlu perhatian')
                ->descriptionIcon($nplRatio <= 5 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($nplColor),
            Stat::make('NPL Outstanding', 'Rp '.number_format($nplOutstanding, 0, ',', '.'))
                ->description('Kol. 3-5')
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger'),
            Stat::make('CKPN Coverage', number_format($ckpnCoverage, 1).'%')
                ->description('Cadangan kerugian')
                ->icon('heroicon-o-shield-check')
                ->color('info'),
        ];
    }
}
