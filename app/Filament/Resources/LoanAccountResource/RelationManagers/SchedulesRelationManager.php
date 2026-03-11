<?php

namespace App\Filament\Resources\LoanAccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Jadwal Angsuran';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('installment_number')
                    ->label('No.')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),
                TextColumn::make('principal_amount')
                    ->label('Pokok')
                    ->money('IDR')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('interest_amount')
                    ->label('Bunga')
                    ->money('IDR')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('principal_paid')
                    ->label('Pokok Dibayar')
                    ->money('IDR'),
                TextColumn::make('interest_paid')
                    ->label('Bunga Dibayar')
                    ->money('IDR'),
                TextColumn::make('outstanding_balance')
                    ->label('Sisa Pokok')
                    ->money('IDR'),
                IconColumn::make('is_paid')
                    ->label('Lunas')
                    ->boolean(),
                TextColumn::make('paid_date')
                    ->label('Tgl. Bayar')
                    ->date('d/m/Y')
                    ->placeholder('-'),
            ])
            ->defaultSort('installment_number');
    }
}
