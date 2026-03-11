<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

class BalanceSheetPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static string|UnitEnum|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationLabel = 'Neraca';

    protected static ?string $title = 'Neraca';

    protected string $view = 'filament.pages.balance-sheet';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('report.view') ?? false;
    }

    public string $reportDate;

    public function mount(): void
    {
        $this->reportDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function balanceSheet(): array
    {
        return app(AccountingReportService::class)->getBalanceSheet(Carbon::parse($this->reportDate));
    }

    public function updatedReportDate(): void
    {
        unset($this->balanceSheet);
    }
}
