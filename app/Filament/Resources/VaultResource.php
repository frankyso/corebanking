<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VaultResource\Pages;
use App\Models\Branch;
use App\Models\User;
use App\Models\Vault;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VaultResource extends Resource
{
    protected static ?string $model = Vault::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|UnitEnum|null $navigationGroup = 'Teller';

    protected static ?int $navigationSort = 31;

    protected static ?string $modelLabel = 'Vault/Brankas';

    protected static ?string $pluralModelLabel = 'Vault/Brankas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Vault')
                    ->description('Data dasar brankas untuk penyimpanan uang kas')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode Vault')
                            ->maxLength(10)
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->label('Nama Vault')
                            ->maxLength(100)
                            ->required(),
                        Select::make('branch_id')
                            ->label('Cabang')
                            ->options(Branch::query()->where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        Select::make('custodian_id')
                            ->label('Custodian')
                            ->options(User::query()->where('is_active', true)->pluck('name', 'id'))
                            ->searchable(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(2),

                Section::make('Ketentuan Saldo')
                    ->description('Batas saldo kas yang diperbolehkan dalam vault')
                    ->schema([
                        TextInput::make('balance')
                            ->label('Saldo Saat Ini')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('minimum_balance')
                            ->label('Saldo Minimum')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('maximum_balance')
                            ->label('Saldo Maksimum')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang'),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('custodian.name')
                    ->label('Custodian')
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVaults::route('/'),
            'create' => Pages\CreateVault::route('/create'),
            'edit' => Pages\EditVault::route('/{record}/edit'),
        ];
    }
}
