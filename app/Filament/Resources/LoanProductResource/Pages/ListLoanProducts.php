<?php

namespace App\Filament\Resources\LoanProductResource\Pages;

use App\Filament\Resources\LoanProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoanProducts extends ListRecords
{
    protected static string $resource = LoanProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
