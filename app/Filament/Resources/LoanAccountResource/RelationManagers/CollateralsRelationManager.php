<?php

namespace App\Filament\Resources\LoanAccountResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollateralsRelationManager extends RelationManager
{
    protected static string $relationship = 'collaterals';

    protected static ?string $title = 'Agunan';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('collateral_type')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(40),
                TextColumn::make('document_number')
                    ->label('No. Dokumen'),
                TextColumn::make('appraised_value')
                    ->label('Nilai Taksasi')
                    ->money('IDR'),
                TextColumn::make('liquidation_value')
                    ->label('Nilai Likuidasi')
                    ->money('IDR'),
                TextColumn::make('ownership_name')
                    ->label('Pemilik'),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->limit(30),
            ]);
    }
}
