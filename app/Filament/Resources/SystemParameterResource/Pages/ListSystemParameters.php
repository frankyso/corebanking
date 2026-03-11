<?php

namespace App\Filament\Resources\SystemParameterResource\Pages;

use App\Filament\Resources\SystemParameterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSystemParameters extends ListRecords
{
    protected static string $resource = SystemParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
