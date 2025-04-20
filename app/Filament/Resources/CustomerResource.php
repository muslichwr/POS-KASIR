<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Customer';
    protected static ?string $pluralModelLabel = 'Customers';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->mask('+62 999-9999-9999')
                            ->prefix('+62')
                            ->placeholder('812-3456-7890'),
                            
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->suffixIcon('heroicon-m-envelope'),
                        
                        Forms\Components\Placeholder::make('total_points')
                            ->label('Total Points')
                            ->content(fn ($record) => $record?->pointsHistory->sum('points_change') ?? 0)
                            ->visibleOn('edit')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->toggleable(isToggledHiddenByDefault: false),
                    
                Tables\Columns\BadgeColumn::make('total_points')
                    ->label('Points')
                    ->color(fn ($state) => $state >= 1000 ? 'success' : 'warning')
                    ->getStateUsing(fn ($record) => $record->pointsHistory->sum('points_change'))
                    ->summarize([
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Points')
                            ->using(fn ($query) => $query->join('customer_points_histories', 'customers.id', '=', 'customer_points_histories.customer_id')
                                ->sum('customer_points_histories.points_change'))
                    ]),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_email')
                ->label('With Email')
                ->query(fn (Builder $query): Builder => $query->whereNotNull('email')),
                
                Tables\Filters\Filter::make('high_value')
                ->label('High Value Customers')
                ->query(fn (Builder $query): Builder => $query->whereHas('pointsHistory', 
                    fn ($q) => $q->selectRaw('customer_id, sum(points_change) as total')
                        ->havingRaw('total >= 1000')
                )),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PointRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
