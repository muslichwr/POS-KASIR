<?php

namespace App\Filament\Resources\InventoryHistoryResource\Pages;

use App\Filament\Resources\InventoryHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryHistories extends ListRecords
{
    protected static string $resource = InventoryHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
