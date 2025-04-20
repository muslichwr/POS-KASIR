<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    #[On('refreshTransactionTotal')]
    public function refreshTransactionTotal(): void
    {
        $transaction = $this->record;
        
        // Calculate total from details
        $totalAmount = $transaction->details()->sum(DB::raw('(price_at_sale - discount_per_item) * quantity'));
        
        // Calculate paid amount from payments
        $paidAmount = $transaction->payments()->sum('amount');
        
        // Update the transaction
        $transaction->update([
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
        ]);
        
        // Refresh the form
        $this->fillForm();
    }
}
