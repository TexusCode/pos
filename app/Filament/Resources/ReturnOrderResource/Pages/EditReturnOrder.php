<?php

namespace App\Filament\Resources\ReturnOrderResource\Pages;

use App\Filament\Resources\ReturnOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReturnOrder extends EditRecord
{
    protected static string $resource = ReturnOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
