<?php

namespace App\Filament\Resources\SavingsAccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Riwayat Transaksi';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Ref')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('transaction_type')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('balance_before')
                    ->label('Saldo Sebelum')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('balance_after')
                    ->label('Saldo Sesudah')
                    ->money('IDR'),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(30),
                TextColumn::make('performer.name')
                    ->label('Petugas'),
                IconColumn::make('is_reversed')
                    ->label('Reversal')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
