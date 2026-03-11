<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Cabang';

    protected static ?string $pluralModelLabel = 'Cabang';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode')
                    ->helperText('3 digit kode unik cabang')
                    ->required()
                    ->maxLength(3)
                    ->minLength(3)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(100),
                Textarea::make('address')
                    ->label('Alamat')
                    ->columnSpanFull(),
                TextInput::make('city')
                    ->label('Kota')
                    ->placeholder('Contoh: Surabaya')
                    ->maxLength(100),
                TextInput::make('province')
                    ->label('Provinsi')
                    ->placeholder('Contoh: Jawa Timur')
                    ->maxLength(100),
                TextInput::make('postal_code')
                    ->label('Kode Pos')
                    ->maxLength(10),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->placeholder('Contoh: 031-1234567')
                    ->maxLength(20),
                Select::make('head_id')
                    ->label('Kepala Cabang')
                    ->relationship('head', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih kepala cabang'),
                Toggle::make('is_head_office')
                    ->label('Kantor Pusat')
                    ->helperText('Tandai jika ini adalah kantor pusat'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
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
                TextColumn::make('city')
                    ->label('Kota')
                    ->sortable(),
                TextColumn::make('head.name')
                    ->label('Kepala Cabang')
                    ->placeholder('Belum ditentukan'),
                IconColumn::make('is_head_office')
                    ->boolean()
                    ->label('KP')
                    ->headerTooltip('Kantor Pusat'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Aktif'),
            ])
            ->filters([
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
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
