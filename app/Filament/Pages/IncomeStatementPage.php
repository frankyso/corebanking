<?php

namespace App\Filament\Pages;

use App\Services\AccountingService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use UnitEnum;

class IncomeStatementPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string|UnitEnum|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 13;

    protected static ?string $navigationLabel = 'Laba Rugi';

    protected static ?string $title = 'Laporan Laba Rugi';

    protected string $view = 'filament.pages.income-statement';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('report.view') ?? false;
    }

    public string $startDate;

    public string $endDate;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function incomeStatement(): array
    {
        return app(AccountingService::class)->getIncomeStatement(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate),
        );
    }

    public function updatedStartDate(): void
    {
        unset($this->incomeStatement);
    }

    public function updatedEndDate(): void
    {
        unset($this->incomeStatement);
    }
}
