<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PhonesRelationManager extends RelationManager
{
    protected static string $relationship = 'phones';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipe Telepon')
                    ->options([
                        'mobile' => 'HP',
                        'home' => 'Rumah',
                        'office' => 'Kantor',
                        'fax' => 'Fax',
                    ])
                    ->required(),
                TextInput::make('number')
                    ->label('Nomor Telepon')
                    ->required()
                    ->tel(),
                Toggle::make('is_primary')
                    ->label('Nomor Utama'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'mobile' => 'HP',
                        'home' => 'Rumah',
                        'office' => 'Kantor',
                        'fax' => 'Fax',
                        default => $state,
                    }),
                TextColumn::make('number')
                    ->label('Nomor'),
                IconColumn::make('is_primary')
                    ->label('Utama')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                DeleteBulkAction::make(),
            ]);
    }
}
