<?php

namespace App\Filament\Resources\TransactionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->label('Produk')
                    ->required(),
                    
                Forms\Components\TextInput::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                    
                Forms\Components\TextInput::make('price_at_sale')
                    ->label('Harga Jual')
                    ->numeric()
                    ->required()
                    ->prefix('Rp'),
                    
                Forms\Components\TextInput::make('discount_per_item')
                    ->label('Diskon per Item')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_id')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('price_at_sale')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable()
                    ->alignRight(),
                    
                Tables\Columns\TextColumn::make('discount_per_item')
                    ->label('Diskon per Item')
                    ->money('IDR')
                    ->sortable()
                    ->alignRight(),
                    
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->alignRight()
                    ->state(fn ($record) => 
                        ($record->price_at_sale - $record->discount_per_item) * $record->quantity
                    ),
           ])
            ->filters([
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->label('Filter Produk'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Item')
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
