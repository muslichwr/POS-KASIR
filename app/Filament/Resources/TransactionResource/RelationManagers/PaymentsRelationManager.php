<?php

namespace App\Filament\Resources\TransactionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('payment_method_id')
                    ->relationship('paymentMethod', 'name')
                    ->required(),
                    
                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah Pembayaran')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->hint(function () {
                        $transaction = $this->getOwnerRecord();
                        $total = $transaction->total_amount ?? 0;
                        $paid = $transaction->payments()->sum('amount') ?? 0;
                        $remaining = $total - $paid;
                        
                        if ($remaining <= 0) return 'Lunas';
                        return 'Sisa: Rp ' . number_format($remaining, 0, ',', '.');
                    })
                    ->extraHintAttributes(['class' => 'text-primary-500']),
                    
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('bayar_penuh')
                        ->label('Bayar Penuh')
                        ->color('success')
                        ->icon('heroicon-m-banknotes')
                        ->action(function (Forms\Set $set) {
                            $transaction = $this->getOwnerRecord();
                            $total = $transaction->total_amount ?? 0;
                            $paid = $transaction->payments()->sum('amount') ?? 0;
                            $remaining = $total - $paid;
                            
                            if ($remaining > 0) {
                                $set('amount', $remaining);
                            }
                        }),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_id')
            ->columns([
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Metode Pembayaran')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->alignRight(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Pembayaran')
                    ->dateTime('d M Y H:i'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Keterangan')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state === 'Pelunasan',
                        'warning' => fn($state) => $state === 'Cicilan',
                    ])
                    ->state(function ($record) {
                        $transaction = $record->transaction;
                        $paymentsBeforeThis = $transaction->payments()
                            ->where('created_at', '<', $record->created_at)
                            ->sum('amount');
                            
                        $totalBeforeThis = $paymentsBeforeThis;
                        
                        if ($totalBeforeThis >= $transaction->total_amount) {
                            return 'Pelunasan';
                        } elseif ($totalBeforeThis + $record->amount >= $transaction->total_amount) {
                            return 'Pelunasan';
                        } else {
                            return 'Cicilan';
                        }
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->relationship('paymentMethod', 'name')
                    ->label('Filter Metode Pembayaran'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Pembayaran')
                    ->after(function ($livewire) {
                        $livewire->dispatch('refreshTransactionTotal');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($livewire) {
                        $livewire->dispatch('refreshTransactionTotal');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($livewire) {
                        $livewire->dispatch('refreshTransactionTotal');
                    }),
            ]);
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make(),
            //     ]),
            // ]);
    }
}
