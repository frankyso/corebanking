<?php

namespace App\Filament\Resources;

use App\Enums\Collectibility;
use App\Enums\LoanStatus;
use App\Filament\Resources\LoanAccountResource\Pages;
use App\Filament\Resources\LoanAccountResource\RelationManagers;
use App\Models\LoanAccount;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LoanAccountResource extends Resource
{
    protected static ?string $model = LoanAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Kredit';

    protected static ?int $navigationSort = 21;

    protected static ?string $modelLabel = 'Rekening Kredit';

    protected static ?string $pluralModelLabel = 'Rekening Kredit';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'active')->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Kredit aktif';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Rekening')
                    ->schema([
                        TextEntry::make('account_number')->label('No. Rekening')->copyable(),
                        TextEntry::make('customer.display_name')->label('Nasabah'),
                        TextEntry::make('loanProduct.name')->label('Produk Kredit'),
                        TextEntry::make('branch.name')->label('Cabang'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('loanOfficer.name')->label('Account Officer')->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Detail Kredit')
                    ->schema([
                        TextEntry::make('principal_amount')->label('Plafon')->money('IDR'),
                        TextEntry::make('interest_rate')->label('Suku Bunga')->suffix('%'),
                        TextEntry::make('tenor_months')->label('Tenor')->suffix(' bulan'),
                        TextEntry::make('disbursement_date')->label('Tanggal Pencairan')->date('d M Y'),
                        TextEntry::make('maturity_date')->label('Jatuh Tempo')->date('d M Y'),
                        TextEntry::make('last_payment_date')->label('Pembayaran Terakhir')->date('d M Y')->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Saldo & Kolektibilitas')
                    ->schema([
                        TextEntry::make('outstanding_principal')->label('Outstanding Pokok')->money('IDR'),
                        TextEntry::make('outstanding_interest')->label('Outstanding Bunga')->money('IDR'),
                        TextEntry::make('accrued_interest')->label('Bunga Akrual')->money('IDR'),
                        TextEntry::make('total_principal_paid')->label('Total Pokok Dibayar')->money('IDR'),
                        TextEntry::make('total_interest_paid')->label('Total Bunga Dibayar')->money('IDR'),
                        TextEntry::make('total_penalty_paid')->label('Total Denda Dibayar')->money('IDR'),
                        TextEntry::make('dpd')->label('DPD (Hari)')
                            ->color(fn (int $state): string => match (true) {
                                $state <= 0 => 'success',
                                $state <= 90 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('collectibility')->label('Kolektibilitas')->badge(),
                        TextEntry::make('ckpn_amount')->label('CKPN')->money('IDR'),
                    ])
                    ->columns(3),
            ]);
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
                        $query->whereHas('customer', function ($q) use ($search): void {
                            $q->where(function ($q) use ($search): void {
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
                    ->headerTooltip('Days Past Due — hari keterlambatan')
                    ->suffix(' hari')
                    ->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'success',
                        $state <= 90 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('collectibility')
                    ->label('Kol.')
                    ->headerTooltip('Kolektibilitas (Kol 1-5)')
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['customer.individualDetail', 'customer.corporateDetail', 'loanProduct']);

        $user = auth()->user();
        if ($user && ! $user->hasAnyRole(['SuperAdmin', 'Auditor', 'Compliance', 'BranchManager'])) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}
