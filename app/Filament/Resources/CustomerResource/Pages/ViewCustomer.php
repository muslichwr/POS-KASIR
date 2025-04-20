<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Customer Profile')
                    ->icon('heroicon-o-user-circle')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Customer Name')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->columnSpan(2),
                            
                        TextEntry::make('created_at')
                            ->label('Member Since')
                            ->dateTime('d M Y')
                            ->since()
                            ->color('gray')
                            ->columnSpan(1),
                            
                        TextEntry::make('total_points')
                            ->label('Total Points')
                            ->numeric()
                            ->state(fn ($record) => $record->pointsHistory->sum('points_change'))
                            ->color(fn ($state) => $state >= 1000 ? 'success' : 'warning')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->icon('heroicon-o-currency-dollar')
                            ->columnSpanFull(),
                    ]),
                    
                Section::make('Contact Information')
                    ->icon('heroicon-o-phone')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('phone')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->url(fn ($state) => "tel:{$state}")
                            ->color('primary'),
                            
                        TextEntry::make('email')
                            ->icon('heroicon-o-envelope')
                            ->url(fn ($state) => "mailto:{$state}")
                            ->color('primary')
                            ->hidden(fn ($state) => !$state),
                    ]),
                    
                Section::make('Points History')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        RepeatableEntry::make('pointsHistory')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Date')
                                            ->dateTime('d M Y H:i')
                                            ->color('gray')
                                            ->columnSpan(1),
                                            
                                        TextEntry::make('source')
                                            ->badge()
                                            ->color(fn ($state) => match($state) {
                                                'transaction' => 'primary',
                                                'promo' => 'success',
                                                'redemption' => 'danger',
                                                'manual' => 'warning',
                                            })
                                            ->formatStateUsing(fn ($state) => ucfirst($state))
                                            ->columnSpan(1),
                                            
                                        TextEntry::make('points_change')
                                            ->label('Points')
                                            ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                                            ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '') . number_format($state))
                                            ->icon(fn ($state) => $state > 0 ? 'heroicon-o-arrow-up' : 'heroicon-o-arrow-down')
                                            ->columnSpan(1),
                                            
                                        TextEntry::make('staff.name')
                                            ->label('Processed By')
                                            ->color('gray')
                                            ->icon('heroicon-o-user')
                                            ->columnSpan(1)
                                            ->hidden(fn ($state) => !$state),
                                    ])
                            ])
                            ->contained(false)
                    ]),
                    
                Section::make('System Information')
                    ->icon('heroicon-o-cpu-chip')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('First Registered')
                                    ->dateTime('D, d M Y H:i')
                                    ->since(),
                                    
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('D, d M Y H:i')
                                    ->since(),
                            ]),
                    ])
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->label('Edit Profile'),
                
            \Filament\Actions\Action::make('addPoints')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->label('Tambah Poin')
                ->form([
                    \Filament\Forms\Components\TextInput::make('points_change')
                        ->label('Jumlah Poin')
                        ->numeric()
                        ->required(),
                        
                    \Filament\Forms\Components\Select::make('source')
                        ->label('Sumber')
                        ->options([
                            'transaction' => 'Transaksi',
                            'promo' => 'Promosi',
                            'redemption' => 'Penukaran',
                        ])
                        ->default('transaction')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->pointsHistory()->create([
                        'points_change' => $data['points_change'],
                        'source' => $data['source'],
                    ]);
                }),
                
            \Filament\Actions\Action::make('reducePoints')
                ->icon('heroicon-o-minus')
                ->color('danger')
                ->label('Kurangi Poin')
                ->form([
                    \Filament\Forms\Components\TextInput::make('points_change')
                        ->label('Jumlah Poin')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                        
                    \Filament\Forms\Components\Select::make('source')
                        ->label('Sumber')
                        ->options([
                            'redemption' => 'Penukaran',
                        ])
                        ->default('redemption')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Ubah nilai poin menjadi negatif
                    $this->record->pointsHistory()->create([
                        'points_change' => -abs($data['points_change']),
                        'source' => $data['source'],
                    ]);
                }),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // CustomerResource\Widgets\PointsBreakdown::class,
            // CustomerResource\Widgets\RecentCustomerActivity::class,
        ];
    }
}