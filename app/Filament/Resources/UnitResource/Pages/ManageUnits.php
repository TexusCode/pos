<?php

namespace App\Filament\Resources\UnitResource\Pages;

use App\Filament\Resources\UnitResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUnits extends ManageRecords
{
    protected static string $resource = UnitResource::class;
    protected static ?string $title = 'Список Единиц Измерения';
    protected ?string $heading = 'Единицы Измерения';
    protected ?string $subheading = 'Определение мерных единиц для товаров';
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
