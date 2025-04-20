<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PointRelationManager extends RelationManager
{
    protected static string $relationship = 'pointsHistory';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('points_change')
                    ->required()
                    ->numeric()
                    ->label('Points Adjustment'),
                    
                Forms\Components\Select::make('source')
                    ->options([
                        'transaction' => 'Transaction',
                        'promo' => 'Promotion',
                        'redemption' => 'Redemption',
                        'manual' => 'Manual Adjustment',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('points_change')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->label('Date'),
                    
                Tables\Columns\TextColumn::make('points_change')
                    ->label('Points Change')
                    ->color(fn ($record) => $record->points_change > 0 ? 'success' : 'danger')
                    ->icon(fn ($record) => $record->points_change > 0 ? 'heroicon-o-arrow-up' : 'heroicon-o-arrow-down')
                    ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '') . number_format($state)),
                    
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'transaction' => 'primary',
                        'promo' => 'success',
                        'redemption' => 'danger',
                        'manual' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                ->options([
                    'transaction' => 'Transaction',
                    'promo' => 'Promotion',
                    'redemption' => 'Redemption',
                    'manual' => 'Manual',
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->label('Manual Adjustment')
                ->icon('heroicon-o-plus'),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
