<?php

namespace App\Filament\Resources\DepositAccountResource\Pages;

use App\Filament\Resources\DepositAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDepositAccounts extends ListRecords
{
    protected static string $resource = DepositAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
