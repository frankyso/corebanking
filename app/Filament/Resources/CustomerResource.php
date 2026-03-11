<?php

namespace App\Filament\Resources;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\RiskRating;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Branch;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('cif_number')
                    ->label('Nomor CIF')
                    ->disabled(),
                Select::make('customer_type')
                    ->label('Tipe Nasabah')
                    ->options(CustomerType::class)
                    ->disabled(),
                Select::make('branch_id')
                    ->label('Cabang')
                    ->options(Branch::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('risk_rating')
                    ->label('Tingkat Risiko')
                    ->options(RiskRating::class),
                Select::make('status')
                    ->label('Status')
                    ->options(CustomerStatus::class)
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cif_number')
                    ->label('CIF')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('customer_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('individualDetail', fn ($q) => $q->where('full_name', 'ilike', "%{$search}%"))
                            ->orWhereHas('corporateDetail', fn ($q) => $q->where('company_name', 'ilike', "%{$search}%"));
                    })
                    ->sortable(query: function ($query, string $direction) {
                        $query->leftJoin('individual_details', 'customers.id', '=', 'individual_details.customer_id')
                            ->leftJoin('corporate_details', 'customers.id', '=', 'corporate_details.customer_id')
                            ->orderByRaw("COALESCE(individual_details.full_name, corporate_details.company_name) {$direction}");
                    }),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('risk_rating')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('customer_type')
                    ->options(CustomerType::class),
                SelectFilter::make('status')
                    ->options(CustomerStatus::class),
                SelectFilter::make('risk_rating')
                    ->options(RiskRating::class),
                SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Branch'),
            ])
            ->recordActions([
                ViewAction::make(),
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
            RelationManagers\AddressesRelationManager::class,
            RelationManagers\PhonesRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['individualDetail', 'corporateDetail', 'branch']);

        $user = auth()->user();
        if ($user && ! $user->hasAnyRole(['SuperAdmin', 'Auditor', 'Compliance', 'BranchManager'])) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}
