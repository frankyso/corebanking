<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipe Alamat')
                    ->options([
                        'domicile' => 'Domisili',
                        'ktp' => 'Sesuai KTP',
                        'office' => 'Kantor',
                        'mailing' => 'Surat Menyurat',
                    ])
                    ->required(),
                Textarea::make('address')
                    ->label('Alamat')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('rt_rw')
                    ->label('RT/RW'),
                TextInput::make('kelurahan')
                    ->label('Kelurahan'),
                TextInput::make('kecamatan')
                    ->label('Kecamatan'),
                TextInput::make('city')
                    ->label('Kota')
                    ->required(),
                TextInput::make('province')
                    ->label('Provinsi')
                    ->required(),
                TextInput::make('postal_code')
                    ->label('Kode Pos')
                    ->maxLength(5),
                Toggle::make('is_primary')
                    ->label('Alamat Utama'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'domicile' => 'Domisili',
                        'ktp' => 'Sesuai KTP',
                        'office' => 'Kantor',
                        'mailing' => 'Surat Menyurat',
                        default => $state,
                    }),
                TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50),
                TextColumn::make('city')
                    ->label('Kota'),
                TextColumn::make('province')
                    ->label('Provinsi'),
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
