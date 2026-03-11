<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemParameterResource\Pages;
use App\Models\SystemParameter;
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
use Filament\Tables\Table;
use UnitEnum;

class SystemParameterResource extends Resource
{
    protected static ?string $model = SystemParameter::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('group')
                    ->required()
                    ->maxLength(50),
                TextInput::make('key')
                    ->required()
                    ->maxLength(100),
                Textarea::make('value')
                    ->required()
                    ->columnSpanFull(),
                Select::make('type')
                    ->options([
                        'string' => 'String',
                        'integer' => 'Integer',
                        'decimal' => 'Decimal',
                        'boolean' => 'Boolean',
                        'date' => 'Date',
                    ])
                    ->default('string')
                    ->required(),
                TextInput::make('description')
                    ->maxLength(255),
                Toggle::make('is_editable')
                    ->label('Editable')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value')
                    ->limit(50),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('description')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_editable')
                    ->boolean()
                    ->label('Editable'),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(fn () => SystemParameter::query()->distinct()->pluck('group', 'group')->toArray()),
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
            'index' => Pages\ListSystemParameters::route('/'),
            'create' => Pages\CreateSystemParameter::route('/create'),
            'edit' => Pages\EditSystemParameter::route('/{record}/edit'),
        ];
    }
}
