<?php

namespace App\Filament\Resources;

use App\Enums\InterestType;
use App\Enums\LoanType;
use App\Filament\Resources\LoanProductResource\Pages;
use App\Models\ChartOfAccount;
use App\Models\LoanProduct;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class LoanProductResource extends Resource
{
    protected static ?string $model = LoanProduct::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'Produk Kredit';

    protected static ?string $pluralModelLabel = 'Produk Kredit';

    public static function form(Schema $schema): Schema
    {
        $glOptions = ChartOfAccount::query()
            ->where('is_header', false)
            ->where('is_active', true)
            ->pluck('account_name', 'id');

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
                        Select::make('loan_type')
                            ->label('Jenis Kredit')
                            ->options(LoanType::class)
                            ->required(),
                        Select::make('interest_type')
                            ->label('Tipe Bunga')
                            ->options(InterestType::class)
                            ->required(),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(2),

                Section::make('Ketentuan Nominal & Tenor')
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
                        TextInput::make('interest_rate')
                            ->label('Suku Bunga (%/tahun)')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        TextInput::make('min_tenor_months')
                            ->label('Tenor Minimal (bulan)')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        TextInput::make('max_tenor_months')
                            ->label('Tenor Maksimal (bulan)')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                    ])->columns(3),

                Section::make('Biaya & Penalti')
                    ->schema([
                        TextInput::make('admin_fee_rate')
                            ->label('Biaya Admin (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0),
                        TextInput::make('provision_fee_rate')
                            ->label('Provisi (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0),
                        TextInput::make('insurance_rate')
                            ->label('Asuransi (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0),
                        TextInput::make('penalty_rate')
                            ->label('Denda Keterlambatan (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0),
                    ])->columns(4),

                Section::make('GL Mapping')
                    ->schema([
                        Select::make('gl_loan_id')
                            ->label('GL Kredit Yang Diberikan')
                            ->options($glOptions)
                            ->searchable(),
                        Select::make('gl_interest_income_id')
                            ->label('GL Pendapatan Bunga')
                            ->options($glOptions)
                            ->searchable(),
                        Select::make('gl_interest_receivable_id')
                            ->label('GL Bunga Yang Masih Harus Diterima')
                            ->options($glOptions)
                            ->searchable(),
                        Select::make('gl_fee_income_id')
                            ->label('GL Pendapatan Provisi/Admin')
                            ->options($glOptions)
                            ->searchable(),
                        Select::make('gl_provision_id')
                            ->label('GL CKPN')
                            ->options($glOptions)
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
                TextColumn::make('loan_type')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('interest_type')
                    ->label('Tipe Bunga')
                    ->badge(),
                TextColumn::make('interest_rate')
                    ->label('Suku Bunga')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('min_amount')
                    ->label('Nominal Min.')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('max_amount')
                    ->label('Nominal Maks.')
                    ->money('IDR')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('loan_type')
                    ->label('Jenis Kredit')
                    ->options(LoanType::class),
                SelectFilter::make('interest_type')
                    ->label('Tipe Bunga')
                    ->options(InterestType::class),
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
            'index' => Pages\ListLoanProducts::route('/'),
            'create' => Pages\CreateLoanProduct::route('/create'),
            'edit' => Pages\EditLoanProduct::route('/{record}/edit'),
        ];
    }
}
