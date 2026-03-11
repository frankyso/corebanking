<?php

namespace App\Filament\Resources;

use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Filament\Resources\DepositAccountResource\Pages;
use App\Filament\Resources\DepositAccountResource\RelationManagers;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DepositAccountResource extends Resource
{
    protected static ?string $model = DepositAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 9;

    protected static ?string $modelLabel = 'Rekening Deposito';

    protected static ?string $pluralModelLabel = 'Rekening Deposito';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'active')->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Deposito aktif';
    }

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
                            ->mapWithKeys(fn (Customer $customer): array => [$customer->id => "{$customer->cif_number} - {$customer->display_name}"])
                    )
                    ->searchable()
                    ->disabled(),
                Select::make('deposit_product_id')
                    ->label('Produk Deposito')
                    ->options(DepositProduct::query()->active()->pluck('name', 'id'))
                    ->disabled(),
                Select::make('status')
                    ->label('Status')
                    ->options(DepositStatus::class)
                    ->disabled(),
                TextInput::make('principal_amount')
                    ->label('Pokok')
                    ->disabled()
                    ->prefix('Rp'),
                TextInput::make('interest_rate')
                    ->label('Suku Bunga')
                    ->disabled()
                    ->suffix('%'),
                TextInput::make('tenor_months')
                    ->label('Tenor (bulan)')
                    ->disabled(),
                Select::make('interest_payment_method')
                    ->label('Metode Pembayaran Bunga')
                    ->options(InterestPaymentMethod::class)
                    ->disabled(),
                Select::make('rollover_type')
                    ->label('Tipe Perpanjangan')
                    ->options(RolloverType::class)
                    ->disabled(),
                DatePicker::make('placement_date')
                    ->label('Tanggal Penempatan')
                    ->disabled(),
                DatePicker::make('maturity_date')
                    ->label('Tanggal Jatuh Tempo')
                    ->disabled(),
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
                    ->searchable(query: function (Builder $query, string $search): void {
                        $query->whereHas('customer', function (\Illuminate\Contracts\Database\Query\Builder $q) use ($search): void {
                            $q->whereHas('individualDetail', fn (\Illuminate\Contracts\Database\Query\Builder $q) => $q->where('full_name', 'ilike', "%{$search}%"))
                                ->orWhereHas('corporateDetail', fn ($q) => $q->where('company_name', 'ilike', "%{$search}%"));
                        });
                    }),
                TextColumn::make('depositProduct.name')
                    ->label('Produk')
                    ->sortable(),
                TextColumn::make('principal_amount')
                    ->label('Pokok')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('interest_rate')
                    ->label('Bunga')
                    ->suffix('%'),
                TextColumn::make('tenor_months')
                    ->label('Tenor')
                    ->suffix(' bln'),
                TextColumn::make('interest_payment_method')
                    ->label('Metode Bunga')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('placement_date')
                    ->label('Tgl Penempatan')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('maturity_date')
                    ->label('Tgl Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(DepositStatus::class),
                SelectFilter::make('deposit_product_id')
                    ->label('Produk')
                    ->relationship('depositProduct', 'name'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name'),
                SelectFilter::make('interest_payment_method')
                    ->label('Metode Bunga')
                    ->options(InterestPaymentMethod::class),
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
            'index' => Pages\ListDepositAccounts::route('/'),
            'create' => Pages\CreateDepositAccount::route('/create'),
            'view' => Pages\ViewDepositAccount::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['customer.individualDetail', 'customer.corporateDetail', 'depositProduct', 'branch']);

        $user = auth()->user();
        if ($user && ! $user->hasAnyRole(['SuperAdmin', 'Auditor', 'Compliance', 'BranchManager'])) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}
