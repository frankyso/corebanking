<?php

namespace App\Filament\Resources\LoanApplicationResource\RelationManagers;

use App\Enums\CollateralType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollateralsRelationManager extends RelationManager
{
    protected static string $relationship = 'collaterals';

    protected static ?string $title = 'Agunan';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('collateral_type')
                    ->label('Jenis Agunan')
                    ->options(CollateralType::class)
                    ->required(),
                TextInput::make('description')
                    ->label('Deskripsi')
                    ->required()
                    ->maxLength(255),
                TextInput::make('document_number')
                    ->label('No. Dokumen')
                    ->maxLength(255),
                TextInput::make('appraised_value')
                    ->label('Nilai Taksasi')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                TextInput::make('liquidation_value')
                    ->label('Nilai Likuidasi')
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('location')
                    ->label('Lokasi')
                    ->maxLength(255),
                TextInput::make('ownership_name')
                    ->label('Nama Pemilik')
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

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
            ]);
    }
}
