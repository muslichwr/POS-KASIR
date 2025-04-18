<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Category Overview')
                    ->columns(3)
                    ->schema([
                        ImageEntry::make('image')
                            ->label('')
                            ->defaultImageUrl(url('/images/default-category.png'))
                            ->disk('public')
                            ->height(200)
                            ->circular()
                            ->columnSpan(1),
                            
                        Section::make()
                            ->columnSpan(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Category Name')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->icon('heroicon-o-tag'),
                                    
                                TextEntry::make('slug')
                                    ->icon('heroicon-m-link')
                                    ->color('gray'),
                                    
                                TextEntry::make('parent.name')
                                    ->label('Parent Category')
                                    ->placeholder('Root Category')
                                    ->url(fn (Category $record): ?string => $record->parent 
                                        ? CategoryResource::getUrl('view', ['record' => $record->parent])
                                        : null
                                    )
                                    ->icon('heroicon-m-arrow-up')
                                    ->color('primary'),
                            ]),
                    ]),
                    
                Tabs::make('Category Details')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->schema([
                                Section::make('Hierarchy Information')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->dateTime()
                                            ->since()
                                            ->color('gray')
                                            ->icon('heroicon-m-calendar'),
                                            
                                        TextEntry::make('updated_at')
                                            ->dateTime()
                                            ->since()
                                            ->color('gray')
                                            ->icon('heroicon-m-clock'),
                                            
                                        TextEntry::make('is_root')
                                            ->label('Root Status')
                                            ->state(fn (Category $record) => $record->isRoot() ? 'Yes' : 'No')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'Yes' => 'success',
                                                'No' => 'gray',
                                            }),
                                    ]),
                            ]),
                            
                        Tabs\Tab::make('Relationships')
                            ->icon('heroicon-m-squares-plus')
                            ->schema([
                                Section::make('Category Hierarchy')
                                    ->description('Category relationships structure')
                                    ->schema([
                                        TextEntry::make('children_count')
                                            ->label('Total Subcategories')
                                            ->state(fn (Category $record) => $record->children()->count())
                                            ->numeric()
                                            ->icon('heroicon-m-arrow-down'),
                                            
                                        TextEntry::make('products_count')
                                            ->label('Total Products')
                                            ->state(fn (Category $record) => $record->products()->count())
                                            ->numeric()
                                            ->icon('heroicon-m-shopping-bag'),
                                    ])
                                    ->columns(2),
                                    
                                Section::make('Parent Category Details')
                                    ->hidden(fn (Category $record) => $record->isRoot())
                                    ->schema([
                                        TextEntry::make('parent.name')
                                            ->label('Parent Category Name')
                                            ->url(fn (Category $record): ?string => $record->parent 
                                                ? CategoryResource::getUrl('view', ['record' => $record->parent])
                                                : null
                                            ),
                                            
                                        TextEntry::make('parent.created_at')
                                            ->label('Parent Created')
                                            ->dateTime()
                                            ->since()
                                            ->color('gray'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(static::$resource::getUrl('index'))
                ->color('secondary'),
                
            Actions\EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->color('primary'),
                
            Actions\DeleteAction::make()
                ->icon('heroicon-m-trash')
                ->modalHeading('Delete Category')
                ->successNotificationTitle('Category deleted successfully'),
        ];
    }
}