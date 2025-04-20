<?php

namespace App\Filament\Resources\InventoryHistoryResource\Pages;

use App\Filament\Resources\InventoryHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateInventoryHistory extends CreateRecord
{
    protected static string $resource = InventoryHistoryResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan user_id selalu ada
        $data['user_id'] = $data['user_id'] ?? Auth::id();
        
        return $data;
    }
}
