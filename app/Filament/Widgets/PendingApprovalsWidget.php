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
            Stat::make('Permohonan Kredit Pending', $pendingLoans)
                ->description('Menunggu persetujuan')
                ->icon('heroicon-o-document-plus')
                ->color($pendingLoans > 0 ? 'warning' : 'success'),
            Stat::make('Jurnal Draft', $pendingJournals)
                ->description('Menunggu posting')
                ->icon('heroicon-o-book-open')
                ->color($pendingJournals > 0 ? 'warning' : 'success'),
        ];
    }
}
