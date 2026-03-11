<?php

namespace App\Filament\Resources\SystemParameterResource\Pages;

use App\Filament\Resources\SystemParameterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSystemParameter extends EditRecord
{
    protected static string $resource = SystemParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
