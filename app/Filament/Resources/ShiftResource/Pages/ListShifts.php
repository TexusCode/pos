<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;
    protected static ?string $title = 'Список Смен';
    protected ?string $heading = 'Смены';
    protected ?string $subheading = 'Управление рабочими сменами сотрудников и кассовыми операциями';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
