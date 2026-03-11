<?php

namespace App\Filament\Resources\DepositProductResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RatesRelationManager extends RelationManager
{
    protected static string $relationship = 'rates';

    protected static ?string $title = 'Suku Bunga';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tenor_months')
                    ->label('Tenor (bulan)')
                    ->numeric()
                    ->required()
                    ->minValue(1),
                TextInput::make('min_amount')
                    ->label('Nominal Minimal')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                TextInput::make('max_amount')
                    ->label('Nominal Maksimal')
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('interest_rate')
                    ->label('Suku Bunga (%)')
                    ->numeric()
                    ->suffix('%')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenor_months')
                    ->label('Tenor (bulan)')
                    ->sortable(),
                TextColumn::make('min_amount')
                    ->label('Nominal Min.')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('max_amount')
                    ->label('Nominal Maks.')
                    ->money('IDR'),
                TextColumn::make('interest_rate')
                    ->label('Suku Bunga')
                    ->suffix('%')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('tenor_months')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
