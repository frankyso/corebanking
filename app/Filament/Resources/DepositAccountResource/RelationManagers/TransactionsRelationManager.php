<?php

namespace App\Filament\Resources\DepositAccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
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
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'placement' => 'Penempatan',
                        'withdrawal' => 'Pencairan',
                        'interest_payment' => 'Pembayaran Bunga',
                        'tax' => 'Pajak',
                        'penalty' => 'Penalti',
                        'rollover' => 'Perpanjangan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'placement' => 'success',
                        'withdrawal' => 'warning',
                        'interest_payment' => 'info',
                        'tax' => 'gray',
                        'penalty' => 'danger',
                        'rollover' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(40),
                TextColumn::make('performer.name')
                    ->label('Petugas'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
