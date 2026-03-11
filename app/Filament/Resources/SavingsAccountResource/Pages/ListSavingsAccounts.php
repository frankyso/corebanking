<?php

namespace App\Filament\Resources\SavingsAccountResource\Pages;

use App\Filament\Resources\SavingsAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavingsAccounts extends ListRecords
{
    protected static string $resource = SavingsAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
