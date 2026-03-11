<?php

namespace App\Filament\Widgets;

use App\Enums\JournalStatus;
use App\Enums\LoanApplicationStatus;
use App\Models\JournalEntry;
use App\Models\LoanApplication;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingApprovalsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $pendingLoans = LoanApplication::query()
            ->whereIn('status', [LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderReview])
            ->count();

        $pendingJournals = JournalEntry::query()
            ->where('status', JournalStatus::Draft)
            ->count();

        return [
            Stat::make('Kredit Pending', $pendingLoans)
                ->description($pendingLoans > 0 ? 'Menunggu persetujuan' : 'Tidak ada pending')
                ->descriptionIcon($pendingLoans > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingLoans > 0 ? 'warning' : 'success'),
            Stat::make('Jurnal Draft', $pendingJournals)
                ->description($pendingJournals > 0 ? 'Menunggu posting' : 'Semua sudah diposting')
                ->descriptionIcon($pendingJournals > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingJournals > 0 ? 'warning' : 'success'),
        ];
    }
}
