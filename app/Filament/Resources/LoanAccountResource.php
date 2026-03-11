<?php

namespace App\Filament\Resources;

use App\Enums\Collectibility;
use App\Enums\LoanStatus;
use App\Filament\Resources\LoanAccountResource\Pages;
use App\Filament\Resources\LoanAccountResource\RelationManagers;
use App\Models\LoanAccount;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LoanAccountResource extends Resource
{
    protected static ?string $model = LoanAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Kredit';

    protected static ?int $navigationSort = 21;

    protected static ?string $modelLabel = 'Rekening Kredit';

    protected static ?string $pluralModelLabel = 'Rekening Kredit';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_number')
                    ->label('No. Rekening')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.cif_number')
                    ->label('CIF')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer')
                    ->label('Nasabah')
                    ->formatStateUsing(function ($record) {
                        return $record->customer?->display_name ?? '-';
                    })
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('customer', function ($q) use ($search) {
                            $q->where(function ($q) use ($search) {
                                $q->whereHas('individualDetail', fn ($q) => $q->where('full_name', 'ilike', "%{$search}%"))
                                    ->orWhereHas('corporateDetail', fn ($q) => $q->where('company_name', 'ilike', "%{$search}%"));
                            });
                        });
                    }),
                TextColumn::make('loanProduct.name')
                    ->label('Produk'),
                TextColumn::make('principal_amount')
                    ->label('Plafon')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('outstanding_principal')
                    ->label('Outstanding')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('interest_rate')
                    ->label('Bunga')
                    ->suffix('%'),
                TextColumn::make('dpd')
                    ->label('DPD')
                    ->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'success',
                        $state <= 90 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('collectibility')
                    ->label('Kol.')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('disbursement_date')
                    ->label('Tgl. Cair')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('maturity_date')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(LoanStatus::class),
                SelectFilter::make('collectibility')
                    ->label('Kolektibilitas')
                    ->options(Collectibility::class),
                SelectFilter::make('loan_product_id')
                    ->label('Produk')
                    ->relationship('loanProduct', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SchedulesRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
            RelationManagers\CollateralsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanAccounts::route('/'),
            'view' => Pages\ViewLoanAccount::route('/{record}'),
        ];
    }
}
