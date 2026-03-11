<?php

namespace App\Filament\Pages;

use App\Enums\Collectibility;
use App\Enums\LoanStatus;
use App\Models\LoanAccount;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

class LoanPortfolioReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 51;

    protected static ?string $navigationLabel = 'Portofolio Kredit';

    protected static ?string $title = 'Laporan Portofolio Kredit';

    protected string $view = 'filament.pages.loan-portfolio-report';

    #[Computed]
    public function portfolioByCollectibility(): Collection
    {
        return LoanAccount::query()
            ->whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])
            ->selectRaw('collectibility, COUNT(*) as count, SUM(outstanding_principal) as total_outstanding, SUM(ckpn_amount) as total_ckpn')
            ->groupBy('collectibility')
            ->orderBy('collectibility')
            ->get()
            ->map(function ($row) {
                $col = Collectibility::from($row->collectibility);

                return [
                    'collectibility' => $col->getLabel(),
                    'color' => $col->getColor(),
                    'count' => $row->count,
                    'total_outstanding' => (float) $row->total_outstanding,
                    'total_ckpn' => (float) $row->total_ckpn,
                    'ckpn_rate' => $col->ckpnRate() * 100,
                ];
            });
    }

    #[Computed]
    public function portfolioByProduct(): Collection
    {
        return LoanAccount::query()
            ->whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])
            ->join('loan_products', 'loan_accounts.loan_product_id', '=', 'loan_products.id')
            ->selectRaw('loan_products.name as product_name, COUNT(*) as count, SUM(outstanding_principal) as total_outstanding, SUM(principal_amount) as total_plafon')
            ->groupBy('loan_products.name')
            ->get();
    }

    #[Computed]
    public function summary(): array
    {
        $active = LoanAccount::whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue]);

        return [
            'total_accounts' => $active->count(),
            'total_outstanding' => (float) $active->sum('outstanding_principal'),
            'total_plafon' => (float) $active->sum('principal_amount'),
            'total_ckpn' => (float) $active->sum('ckpn_amount'),
            'npl_count' => LoanAccount::whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])
                ->where('collectibility', '>=', 3)->count(),
            'npl_amount' => (float) LoanAccount::whereIn('status', [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])
                ->where('collectibility', '>=', 3)->sum('outstanding_principal'),
        ];
    }
}
