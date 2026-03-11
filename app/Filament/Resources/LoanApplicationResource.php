<?php

namespace App\Filament\Resources;

use App\Enums\LoanApplicationStatus;
use App\Filament\Resources\LoanApplicationResource\Pages;
use App\Filament\Resources\LoanApplicationResource\RelationManagers;
use App\Models\LoanApplication;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LoanApplicationResource extends Resource
{
    protected static ?string $model = LoanApplication::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Kredit';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Permohonan Kredit';

    protected static ?string $pluralModelLabel = 'Permohonan Kredit';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application_number')
                    ->label('No. Permohonan')
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
                TextColumn::make('requested_amount')
                    ->label('Jumlah Diminta')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('approved_amount')
                    ->label('Jumlah Disetujui')
                    ->money('IDR')
                    ->placeholder('-'),
                TextColumn::make('requested_tenor_months')
                    ->label('Tenor')
                    ->suffix(' bln'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Tanggal Pengajuan')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(LoanApplicationStatus::class),
                SelectFilter::make('loan_product_id')
                    ->label('Produk')
                    ->relationship('loanProduct', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
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
            RelationManagers\CollateralsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanApplications::route('/'),
            'create' => Pages\CreateLoanApplication::route('/create'),
            'view' => Pages\ViewLoanApplication::route('/{record}'),
        ];
    }
}
