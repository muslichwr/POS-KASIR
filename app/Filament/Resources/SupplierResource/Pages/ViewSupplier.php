<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Supplier Overview')
                    ->icon('heroicon-o-information-circle')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Supplier Name')
                            ->weight('bold')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->columnSpan(2),
                    ]),
                    
                Section::make('Contact Details')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('contact_info')
                                    ->label('Full Contact Information')
                                    ->formatStateUsing(function ($state) {
                                        return new HtmlString(
                                            '<div class="space-y-2">' . 
                                            nl2br(e($state)) . 
                                            '</div>'
                                        );
                                    })
                                    ->columnSpan(1)
                                    ->prose(),
                                    
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('contact_person')
                                            ->label('Primary Contact')
                                            ->state(function ($record) {
                                                $lines = explode("\n", $record->contact_info);
                                                return $lines[0] ?? 'N/A';
                                            })
                                            ->icon('heroicon-o-user'),
                                            
                                        TextEntry::make('phone')
                                            ->label('Contact Number')
                                            ->state(function ($record) {
                                                $lines = explode("\n", $record->contact_info);
                                                return preg_match('/Phone:\s*(.+)/', $lines[1] ?? '', $matches) 
                                                    ? $matches[1] 
                                                    : 'N/A';
                                            })
                                            ->icon('heroicon-o-phone')
                                            ->url(fn ($state) => "tel:$state"),
                                            
                                        TextEntry::make('email')
                                            ->label('Email Address')
                                            ->state(function ($record) {
                                                $lines = explode("\n", $record->contact_info);
                                                return preg_match('/Email:\s*(.+)/', $lines[2] ?? '', $matches) 
                                                    ? $matches[1] 
                                                    : 'N/A';
                                            })
                                            ->icon('heroicon-o-envelope')
                                            ->url(fn ($state) => "mailto:$state"),
                                            
                                        TextEntry::make('address')
                                            ->label('Physical Address')
                                            ->state(function ($record) {
                                                $lines = explode("\n", $record->contact_info);
                                                return $lines[3] ?? 'N/A';
                                            })
                                            ->icon('heroicon-o-map-pin'),
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ]),
                    
                Section::make('System Information')
                    ->icon('heroicon-o-computer-desktop')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('First Registered')
                                    ->dateTime('D, d M Y H:i')
                                    ->since()
                                    ->icon('heroicon-o-clock'),
                                    
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('D, d M Y H:i')
                                    ->since()
                                    ->icon('heroicon-o-arrow-path'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->label('Edit Supplier'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // SupplierResource\Widgets\SupplierStats::class,
        ];
    }
}