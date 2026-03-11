<?php

namespace App\Filament\Resources\DepositProductResource\Pages;

use App\Filament\Resources\DepositProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDepositProduct extends EditRecord
{
    protected static string $resource = DepositProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
