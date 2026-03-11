<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipe Dokumen')
                    ->options([
                        'ktp' => 'KTP',
                        'npwp' => 'NPWP',
                        'sim' => 'SIM',
                        'passport' => 'Paspor',
                        'akta' => 'Akta Pendirian',
                        'siup' => 'SIUP',
                        'tdp' => 'TDP',
                        'nib' => 'NIB',
                        'sk_kemenkumham' => 'SK Kemenkumham',
                        'other' => 'Lainnya',
                    ])
                    ->required(),
                TextInput::make('document_number')
                    ->label('Nomor Dokumen')
                    ->required(),
                FileUpload::make('file_path')
                    ->label('File Dokumen')
                    ->directory('customer-documents')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(5120),
                TextInput::make('file_name')
                    ->label('Nama File')
                    ->helperText('Terisi otomatis saat upload'),
                DatePicker::make('expiry_date')
                    ->label('Tanggal Kedaluwarsa'),
                Toggle::make('is_verified')
                    ->label('Terverifikasi'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ktp' => 'KTP',
                        'npwp' => 'NPWP',
                        'sim' => 'SIM',
                        'passport' => 'Paspor',
                        'akta' => 'Akta Pendirian',
                        'siup' => 'SIUP',
                        'tdp' => 'TDP',
                        'nib' => 'NIB',
                        'sk_kemenkumham' => 'SK Kemenkumham',
                        default => $state,
                    }),
                TextColumn::make('document_number')
                    ->label('Nomor'),
                TextColumn::make('expiry_date')
                    ->label('Kedaluwarsa')
                    ->date('d M Y'),
                IconColumn::make('is_verified')
                    ->label('Terverifikasi')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                DeleteBulkAction::make(),
            ]);
    }
}
