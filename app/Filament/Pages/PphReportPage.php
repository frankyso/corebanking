<?php

namespace App\Filament\Pages;

use App\Actions\Tax\GeneratePphReport;
use App\DTOs\Tax\PphReportData;
use App\DTOs\Tax\PphReportResult;
use App\Models\Branch;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class PphReportPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 52;

    protected static ?string $navigationLabel = 'Laporan PPh';

    protected static ?string $title = 'Laporan PPh Bunga';

    protected ?string $subheading = 'Ringkasan pemotongan PPh atas bunga tabungan dan deposito';

    protected string $view = 'filament.pages.pph-report';

    public int $year;

    public int $month = 0;

    public string $productType = '';

    public ?int $branchId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('report.view') ?? false;
    }

    public function mount(): void
    {
        $this->year = (int) now()->year;
    }

    #[Computed]
    public function report(): PphReportResult
    {
        return app(GeneratePphReport::class)->execute(
            new PphReportData(
                year: $this->year,
                month: $this->month,
                productType: $this->productType !== '' ? $this->productType : null,
                branchId: $this->branchId,
            )
        );
    }

    /**
     * @return Collection<int|string, mixed>
     */
    #[Computed]
    public function branches(): Collection
    {
        return Branch::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function updatedYear(): void
    {
        unset($this->report);
    }

    public function updatedMonth(): void
    {
        unset($this->report);
    }

    public function updatedProductType(): void
    {
        unset($this->report);
    }

    public function updatedBranchId(): void
    {
        unset($this->report);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Ekspor CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $reportData = app(GeneratePphReport::class)->execute(
            new PphReportData(
                year: $this->year,
                month: $this->month,
                productType: $this->productType !== '' ? $this->productType : null,
                branchId: $this->branchId,
            )
        );

        $periodLabel = $this->month > 0
            ? sprintf('%04d-%02d', $this->year, $this->month)
            : (string) $this->year;

        return response()->streamDownload(function () use ($reportData): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['No', 'Nama', 'NPWP', 'Tipe', 'Produk', 'Bunga Bruto', 'PPh', 'Bunga Neto']);

            $no = 1;
            foreach ($reportData->customerBreakdown as $row) {
                fputcsv($handle, [
                    $no++,
                    (string) $row['customer_name'],
                    (string) ($row['npwp'] ?? '-'),
                    (string) $row['customer_type'],
                    (string) $row['product_type'],
                    number_format((float) $row['gross_interest'], 2, '.', ''),
                    number_format((float) $row['tax_amount'], 2, '.', ''),
                    number_format((float) $row['net_interest'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, [
                '',
                'TOTAL',
                '',
                '',
                '',
                number_format($reportData->totalGrossInterest, 2, '.', ''),
                number_format($reportData->totalTax, 2, '.', ''),
                number_format($reportData->totalNetInterest, 2, '.', ''),
            ]);

            fclose($handle);
        }, "laporan-pph-{$periodLabel}.csv");
    }
}
