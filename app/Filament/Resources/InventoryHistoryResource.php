<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryHistoryResource\Pages;
use App\Filament\Resources\InventoryHistoryResource\RelationManagers;
use App\Models\InventoryHistory;
use App\Models\Product;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class InventoryHistoryResource extends Resource
{
    protected static ?string $model = InventoryHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Stock Movement';
    protected static ?string $pluralModelLabel = 'Inventory History';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stock Movement Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'),
                            
                        Forms\Components\Select::make('type')
                            ->options([
                                'initial' => 'Initial Stock',
                                'sale' => 'Sales Deduction',
                                'restock' => 'Restock',
                                'adjustment' => 'Manual Adjustment',
                            ])
                            ->required()
                            ->native(false)
                            ->disabledOn('edit'),
                            
                        Forms\Components\TextInput::make('quantity_change')
                            ->required()
                            ->numeric()
                            ->label('Quantity Change')
                            ->rules([
                                fn ($get) => function ($attribute, $value, $fail) use ($get) {
                                    if ($get('type') === 'sale' && $value > 0) {
                                        $fail('Quantity change for sales must be negative');
                                    }
                                }
                            ]),
                            
                        Forms\Components\TextInput::make('cost_price')
                            ->numeric()
                            ->prefix('Rp')
                            ->nullable(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull()
                            ->nullable(),
                            
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id())
                            ->dehydrated(true)
                            ->required(),
                    ]),
                    
                Forms\Components\Section::make('System Information')
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn ($record) => $record?->created_at?->format('d M Y H:i') ?? '-'),
                            
                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last Updated')
                            ->content(fn ($record) => $record?->updated_at?->format('d M Y H:i') ?? '-'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => ProductResource::getUrl('edit', [$record->product_id])),
                    
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'initial' => 'Initial',
                        'sale' => 'Sale',
                        'restock' => 'Restock',
                        'adjustment' => 'Adjustment',
                    })
                    ->colors([
                        'primary' => fn($state) => $state === 'initial',
                        'danger' => fn($state) => $state === 'sale',
                        'success' => fn($state) => $state === 'restock',
                        'warning' => fn($state) => $state === 'adjustment',
                    ]),
                    
                Tables\Columns\TextColumn::make('quantity_change')
                    ->label('Qty Change')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "+$state" : $state)
                    ->extraAttributes(fn ($state) => [
                        'class' => $state > 0 ? 'text-success-500' : 'text-danger-500',
                    ])
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('cost_price')
                    ->money('IDR')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Processed By')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'initial' => 'Initial Stock',
                        'sale' => 'Sales',
                        'restock' => 'Restocks',
                        'adjustment' => 'Adjustments',
                    ]),
                    
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name'),
                    
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name'),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->type === 'adjustment'),
                    
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            // Filter records to only include adjustments
                            $adjustmentRecords = $records->filter(fn ($record) => $record->type === 'adjustment');
                            
                            // Delete only adjustment records
                            $adjustmentRecords->each->delete();
                            
                            // Return notification
                            if ($adjustmentRecords->count() !== $records->count()) {
                                Notification::make()
                                    ->warning()
                                    ->title('Some records were not deleted')
                                    ->body('Only adjustment records can be deleted.')
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record) => self::getUrl('view', [$record]));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryHistories::route('/'),
            'create' => Pages\CreateInventoryHistory::route('/create'),
            'view' => Pages\ViewInventoryHistory::route('/{record}'),
            'edit' => Pages\EditInventoryHistory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count();
    }
}