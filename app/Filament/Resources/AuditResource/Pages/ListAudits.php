<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;
    protected static ?string $title = 'Список Ревизий';
    protected ?string $heading = 'Ревизии';
    protected ?string $subheading = 'Управление инвентаризацией и проверка складских остатков';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
