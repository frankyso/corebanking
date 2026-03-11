<?php

namespace App\Filament\Resources;

use App\Enums\SavingsAccountStatus;
use App\Filament\Resources\SavingsAccountResource\Pages;
use App\Filament\Resources\SavingsAccountResource\RelationManagers;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
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

class SavingsAccountResource extends Resource
{
    protected static ?string $model = SavingsAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Rekening Tabungan';

    protected static ?string $pluralModelLabel = 'Rekening Tabungan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('account_number')
                    ->label('Nomor Rekening')
                    ->disabled(),
                Select::make('customer_id')
                    ->label('Nasabah')
                    ->options(
                        Customer::query()
                            ->active()
                            ->with(['individualDetail', 'corporateDetail'])
                            ->get()
                            ->mapWithKeys(fn (Customer $customer) => [$customer->id => "{$customer->cif_number} - {$customer->display_name}"])
                    )
                    ->searchable()
                    ->required(),
                Select::make('savings_product_id')
                    ->label('Produk Tabungan')
                    ->options(SavingsProduct::query()->active()->pluck('name', 'id'))
                    ->required(),
                Select::make('branch_id')
                    ->label('Cabang')
                    ->options(Branch::query()->where('is_active', true)->pluck('name', 'id'))
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(SavingsAccountStatus::class)
                    ->disabled(),
                TextInput::make('balance')
                    ->label('Saldo')
                    ->disabled()
                    ->prefix('Rp'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_number')
                    ->label('No. Rekening')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('customer.cif_number')
                    ->label('CIF')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.display_name')
                    ->label('Nasabah')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('customer', function ($q) use ($search) {
                            $q->whereHas('individualDetail', fn ($q) => $q->where('full_name', 'ilike', "%{$search}%"))
                                ->orWhereHas('corporateDetail', fn ($q) => $q->where('company_name', 'ilike', "%{$search}%"));
                        });
                    }),
                TextColumn::make('savingsProduct.name')
                    ->label('Produk')
                    ->sortable(),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('hold_amount')
                    ->label('Hold')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('available_balance')
                    ->label('Saldo Tersedia')
                    ->money('IDR'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('opened_at')
                    ->label('Tanggal Buka')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(SavingsAccountStatus::class),
                SelectFilter::make('savings_product_id')
                    ->label('Produk')
                    ->relationship('savingsProduct', 'name'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavingsAccounts::route('/'),
            'create' => Pages\CreateSavingsAccount::route('/create'),
            'view' => Pages\ViewSavingsAccount::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['customer.individualDetail', 'customer.corporateDetail', 'savingsProduct', 'branch']);

        $user = auth()->user();
        if ($user && ! $user->hasAnyRole(['SuperAdmin', 'Auditor', 'Compliance', 'BranchManager'])) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}
