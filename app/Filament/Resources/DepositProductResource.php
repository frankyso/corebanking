<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepositProductResource\Pages;
use App\Filament\Resources\DepositProductResource\RelationManagers;
use App\Models\ChartOfAccount;
use App\Models\DepositProduct;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class DepositProductResource extends Resource
{
    protected static ?string $model = DepositProduct::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Produk Deposito';

    protected static ?string $pluralModelLabel = 'Produk Deposito';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Produk')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode Produk')
                            ->maxLength(3)
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->maxLength(100)
                            ->required(),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('currency')
                            ->label('Mata Uang')
                            ->default('IDR')
                            ->maxLength(3),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(2),

                Section::make('Ketentuan Nominal')
                    ->schema([
                        TextInput::make('min_amount')
                            ->label('Nominal Minimal')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        TextInput::make('max_amount')
                            ->label('Nominal Maksimal')
                            ->numeric()
                            ->prefix('Rp'),
                    ])->columns(2),

                Section::make('Penalti & Pajak')
                    ->schema([
                        TextInput::make('penalty_rate')
                            ->label('Tarif Penalti Pencairan Dini (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0.5),
                        TextInput::make('tax_rate')
                            ->label('Tarif Pajak (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(20),
                        TextInput::make('tax_threshold')
                            ->label('Batas Nominal Kena Pajak')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(7500000),
                    ])->columns(3),

                Section::make('GL Mapping')
                    ->schema([
                        Select::make('gl_deposit_id')
                            ->label('GL Deposito')
                            ->options(ChartOfAccount::query()->where('is_header', false)->where('is_active', true)->pluck('account_name', 'id'))
                            ->searchable(),
                        Select::make('gl_interest_expense_id')
                            ->label('GL Beban Bunga')
                            ->options(ChartOfAccount::query()->where('is_header', false)->where('is_active', true)->pluck('account_name', 'id'))
                            ->searchable(),
                        Select::make('gl_interest_payable_id')
                            ->label('GL Bunga Yang Masih Harus Dibayar')
                            ->options(ChartOfAccount::query()->where('is_header', false)->where('is_active', true)->pluck('account_name', 'id'))
                            ->searchable(),
                        Select::make('gl_tax_payable_id')
                            ->label('GL Hutang Pajak')
                            ->options(ChartOfAccount::query()->where('is_header', false)->where('is_active', true)->pluck('account_name', 'id'))
                            ->searchable(),
                    ])->columns(2),
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
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('min_amount')
                    ->label('Nominal Min.')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('max_amount')
                    ->label('Nominal Maks.')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('penalty_rate')
                    ->label('Penalti')
                    ->suffix('%'),
                TextColumn::make('tax_rate')
                    ->label('Pajak')
                    ->suffix('%'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepositProducts::route('/'),
            'create' => Pages\CreateDepositProduct::route('/create'),
            'edit' => Pages\EditDepositProduct::route('/{record}/edit'),
        ];
    }
}
