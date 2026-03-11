<?php

namespace App\Filament\Resources\JournalEntryResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Detail Jurnal';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('chartOfAccount.account_code')
                    ->label('Kode Akun'),
                TextColumn::make('chartOfAccount.account_name')
                    ->label('Nama Akun'),
                TextColumn::make('description')
                    ->label('Keterangan'),
                TextColumn::make('debit')
                    ->label('Debit')
                    ->money('IDR')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('credit')
                    ->label('Kredit')
                    ->money('IDR')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
            ]);
    }
}
