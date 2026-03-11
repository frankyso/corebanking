<?php

namespace App\Filament\Resources;

use App\Enums\InterestCalcMethod;
use App\Filament\Resources\SavingsProductResource\Pages;
use App\Models\ChartOfAccount;
use App\Models\SavingsProduct;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class SavingsProductResource extends Resource
{
    protected static ?string $model = SavingsProduct::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Produk Tabungan';

    protected static ?string $pluralModelLabel = 'Produk Tabungan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Produk')
                    ->description('Identitas dan ketentuan umum produk tabungan')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode Produk')
                            ->helperText('3 digit kode unik produk')
                            ->maxLength(3)
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->maxLength(100)
                            ->required(),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3),
                        Select::make('interest_calc_method')
                            ->label('Metode Perhitungan Bunga')
                            ->options(InterestCalcMethod::class)
                            ->required(),
                        TextInput::make('interest_rate')
                            ->label('Suku Bunga (%)')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(2),

                Section::make('Ketentuan Saldo')
                    ->description('Batasan nominal tabungan')
                    ->schema([
                        TextInput::make('min_opening_balance')
                            ->label('Setoran Awal Minimal')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('min_balance')
                            ->label('Saldo Minimal')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('max_balance')
                            ->label('Saldo Maksimal')
                            ->numeric()
                            ->prefix('Rp'),
                    ])->columns(3),

                Section::make('Biaya & Pajak')
                    ->description('Biaya administrasi, penutupan, dormant, dan ketentuan pajak bunga')
                    ->schema([
                        TextInput::make('admin_fee_monthly')
                            ->label('Biaya Admin Bulanan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('closing_fee')
                            ->label('Biaya Penutupan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('dormant_fee')
                            ->label('Biaya Dormant')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('dormant_period_days')
                            ->label('Periode Dormant (hari)')
                            ->numeric()
                            ->default(365),
                        TextInput::make('tax_rate')
                            ->label('Tarif Pajak (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(20),
                        TextInput::make('tax_threshold')
                            ->label('Batas Saldo Kena Pajak')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(7500000),
                    ])->columns(3),

                Section::make('GL Mapping')
                    ->description('Mapping ke bagan akun untuk pencatatan otomatis')
                    ->schema([
                        Select::make('gl_savings_id')
                            ->label('GL Tabungan')
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
                        Select::make('gl_admin_fee_income_id')
                            ->label('GL Pendapatan Admin')
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
                TextColumn::make('interest_calc_method')
                    ->label('Metode Bunga')
                    ->badge(),
                TextColumn::make('interest_rate')
                    ->label('Suku Bunga')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('min_opening_balance')
                    ->label('Setoran Awal Min.')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('admin_fee_monthly')
                    ->label('Biaya Admin')
                    ->money('IDR'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('interest_calc_method')
                    ->label('Metode Bunga')
                    ->options(InterestCalcMethod::class),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavingsProducts::route('/'),
            'create' => Pages\CreateSavingsProduct::route('/create'),
            'edit' => Pages\EditSavingsProduct::route('/{record}/edit'),
        ];
    }
}
