<?php

namespace App\Filament\Resources;

use App\Enums\AccountGroup;
use App\Enums\NormalBalance;
use App\Filament\Resources\ChartOfAccountResource\Pages;
use App\Models\ChartOfAccount;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class ChartOfAccountResource extends Resource
{
    protected static ?string $model = ChartOfAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'account_name';

    protected static ?string $modelLabel = 'Bagan Akun';

    protected static ?string $pluralModelLabel = 'Bagan Akun';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('account_code')
                    ->label('Kode Akun')
                    ->required()
                    ->maxLength(12)
                    ->unique(ignoreRecord: true)
                    ->placeholder('1.01.01.000'),
                TextInput::make('account_name')
                    ->label('Nama Akun')
                    ->required()
                    ->maxLength(150),
                Select::make('account_group')
                    ->label('Kelompok Akun')
                    ->options(AccountGroup::class)
                    ->required(),
                Select::make('parent_id')
                    ->label('Akun Induk')
                    ->relationship('parent', 'account_name')
                    ->getOptionLabelFromRecordUsing(fn (ChartOfAccount $record): string => "{$record->account_code} - {$record->account_name}")
                    ->searchable()
                    ->preload(),
                TextInput::make('level')
                    ->label('Level')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(4),
                Select::make('normal_balance')
                    ->label('Saldo Normal')
                    ->options(NormalBalance::class)
                    ->required(),
                Toggle::make('is_header')
                    ->label('Akun Header'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Textarea::make('description')
                    ->label('Keterangan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_code')
                    ->label('Kode Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_name')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_group')
                    ->label('Kelompok')
                    ->badge()
                    ->sortable(),
                TextColumn::make('level')
                    ->label('Level')
                    ->sortable(),
                TextColumn::make('normal_balance')
                    ->label('Saldo Normal')
                    ->badge(),
                IconColumn::make('is_header')
                    ->boolean()
                    ->label('Header'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Aktif'),
            ])
            ->defaultSort('account_code')
            ->filters([
                SelectFilter::make('account_group')
                    ->options(AccountGroup::class),
                TernaryFilter::make('is_header')
                    ->label('Hanya Header'),
                TernaryFilter::make('is_active')
                    ->label('Aktif'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChartOfAccounts::route('/'),
            'create' => Pages\CreateChartOfAccount::route('/create'),
            'edit' => Pages\EditChartOfAccount::route('/{record}/edit'),
        ];
    }
}
