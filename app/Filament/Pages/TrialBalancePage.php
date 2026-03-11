<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

class TrialBalancePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Neraca Saldo';

    protected static ?string $title = 'Neraca Saldo';

    protected string $view = 'filament.pages.trial-balance';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('report.view') ?? false;
    }

    public int $year;

    public int $month;

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    #[Computed]
    public function trialBalance(): Collection
    {
        return app(AccountingReportService::class)->getTrialBalance($this->year, $this->month);
    }

    public function updatedYear(): void
    {
        unset($this->trialBalance);
    }

    public function updatedMonth(): void
    {
        unset($this->trialBalance);
    }
}
