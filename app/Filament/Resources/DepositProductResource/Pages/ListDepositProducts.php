<?php

namespace App\Filament\Resources\DepositProductResource\Pages;

use App\Filament\Resources\DepositProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDepositProducts extends ListRecords
{
    protected static string $resource = DepositProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
