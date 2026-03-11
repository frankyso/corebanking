<?php

namespace App\Filament\Widgets;

use App\Enums\Collectibility;
use App\Models\LoanAccount;
use Filament\Widgets\ChartWidget;

class LoanPortfolioChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Kredit per Kolektibilitas';

    protected ?string $description = 'Sebaran outstanding kredit per tingkat kolektibilitas';

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        $colors = [];

        foreach (Collectibility::cases() as $col) {
            $amount = (float) LoanAccount::query()
                ->active()
                ->where('collectibility', $col)
                ->sum('outstanding_principal');

            $data[] = $amount;
            $labels[] = $col->getLabel();
            $colors[] = match ($col) {
                Collectibility::Current => '#22c55e',
                Collectibility::SpecialMention => '#eab308',
                Collectibility::Substandard => '#f97316',
                Collectibility::Doubtful => '#ef4444',
                Collectibility::Loss => '#6b7280',
            };
        }

        return [
            'datasets' => [
                [
                    'label' => 'Outstanding',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
