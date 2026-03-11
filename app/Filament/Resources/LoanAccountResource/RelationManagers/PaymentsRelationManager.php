<?php

namespace App\Filament\Resources\LoanAccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Riwayat Pembayaran';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('No. Referensi')
                    ->searchable(),
                TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('payment_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'installment' => 'Angsuran',
                        'early_payment' => 'Pelunasan Dini',
                        'penalty' => 'Denda',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'installment' => 'success',
                        'early_payment' => 'info',
                        'penalty' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label('Total')
                    ->money('IDR')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('principal_portion')
                    ->label('Pokok')
                    ->money('IDR')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('interest_portion')
                    ->label('Bunga')
                    ->money('IDR')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('penalty_portion')
                    ->label('Denda')
                    ->money('IDR'),
                TextColumn::make('performer.name')
                    ->label('Petugas'),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(30),
            ])
            ->defaultSort('payment_date', 'desc');
    }
}
