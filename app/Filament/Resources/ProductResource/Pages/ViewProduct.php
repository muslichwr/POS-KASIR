<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\SupplierResource;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Product Overview')
                    ->icon('heroicon-o-shopping-bag')
                    ->columns(3)
                    ->schema([
                        ImageEntry::make('image')
                            ->label('')
                            ->defaultImageUrl(url('/images/default-product.png'))
                            ->disk('public')
                            ->height(300)
                            ->circular()
                            ->columnSpan(1),
                            
                        Grid::make(2)
                            ->columnSpan(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Product Name')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->columnSpanFull(),
                                    
                                TextEntry::make('slug')
                                    ->color('gray')
                                    ->badge()
                                    ->columnSpanFull(),
                            ]),
                    ]),
                    
                Section::make('Pricing & Stock')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('price')
                            ->money('IDR')
                            ->color('success')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->icon('heroicon-o-tag'),
                            
                        TextEntry::make('cost_price')
                            ->label('Cost Price')
                            ->money('IDR')
                            ->color('danger')
                            ->icon('heroicon-o-banknotes'),
                            
                        TextEntry::make('stock')
                            ->numeric()
                            ->icon('heroicon-o-archive-box')
                            ->color(function ($state) {
                                return $state > 10 ? 'success' : 'danger';
                            })
                            ->formatStateUsing(fn ($state) => "{$state} units in stock")
                            ->badge()
                            ->iconColor(function ($state) {
                                return $state > 10 ? 'success' : 'danger';
                            }),
                            
                        TextEntry::make('profit_margin')
                            ->label('Profit Margin')
                            ->state(function ($record) {
                                $margin = $record->price - $record->cost_price;
                                $percentage = ($margin / $record->cost_price) * 100;
                                return number_format($percentage, 2).'%';
                            })
                            ->color(function ($state) {
                                $percentage = (float) str_replace('%', '', $state);
                                return ($percentage >= 30) ? 'success' : ($percentage >= 15 ? 'warning' : 'danger');
                            })
                            ->icon('heroicon-o-chart-bar'),
                    ]),
                Section::make('Supplier & Category')
                    ->icon('heroicon-o-tag')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('supplier.name')
                            ->label('Supplier')
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-o-truck')
                            ->url(fn ($record) => SupplierResource::getUrl('view', [$record->supplier_id])),
                            
                        TextEntry::make('category.name')
                            ->label('Category')
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-o-tag')
                            ->url(fn ($record) => CategoryResource::getUrl('view', [$record->category_id])),
                            
                        TextEntry::make('category_products')
                            ->label('Products in Category')
                            ->state(function ($record) {
                                return $record->category->products()->count();
                            })
                            ->icon('heroicon-o-square-3-stack-3d')
                            ->color('gray'),
                    ]),
                    
                Section::make('System Information')
                    ->icon('heroicon-o-computer-desktop')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('First Created')
                                    ->dateTime('d M Y H:i')
                                    ->since()
                                    ->icon('heroicon-o-clock'),
                                    
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('d M Y H:i')
                                    ->since()
                                    ->icon('heroicon-o-arrow-path'),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->label('Edit Product')
                ->color('gray'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // ProductResource\Widgets\ProductSalesWidget::class,
            // ProductResource\Widgets\StockHistoryWidget::class,
        ];
    }
}