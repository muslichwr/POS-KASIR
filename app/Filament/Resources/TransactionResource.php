<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'transaction_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('phone')
                                    ->required(),
                            ]),
                            
                        Forms\Components\DateTimePicker::make('transaction_date')
                            ->default(now())
                            ->required(),
                            
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id())
                            ->dehydrated(true)
                            ->required(),
                            
                        Forms\Components\Hidden::make('total_amount')
                            ->default('0')
                            ->dehydrated(true)
                            ->required(),
                            
                        Forms\Components\Hidden::make('paid_amount')
                            ->default('0')
                            ->dehydrated(true)
                            ->required(),
                            
                        Forms\Components\Repeater::make('details')
                            ->relationship()
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->options(Product::all()->pluck('name', 'id'))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('price_at_sale', $product->price);
                                        }
                                    }),
                                    
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                                    
                                Forms\Components\TextInput::make('price_at_sale')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp'),
                                    
                                Forms\Components\TextInput::make('discount_per_item')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                            ])
                            ->live()
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotals($get, $set);
                            }),
                            
                        Forms\Components\Repeater::make('payments')
                            ->relationship()
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\Select::make('payment_method_id')
                                    ->relationship('paymentMethod', 'name')
                                    ->required(),
                                    
                                Forms\Components\TextInput::make('amount')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp')
                                    ->hint(function ($get) {
                                        $remaining = $get('../../total_amount') - $get('../../paid_amount');
                                        if ($remaining <= 0) return 'Lunas';
                                        return 'Sisa: Rp ' . number_format($remaining, 0, ',', '.');
                                    })
                                    ->hintColor('primary')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('bayar_penuh')
                                            ->icon('heroicon-m-banknotes')
                                            ->tooltip('Bayar Penuh')
                                            ->action(function ($get, $set) {
                                                $remaining = $get('../../total_amount') - $get('../../paid_amount');
                                                if ($remaining > 0) {
                                                    $set('amount', $remaining);
                                                }
                                            })
                                    ),
                            ])
                            ->live()
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotals($get, $set);
                            }),
                            
                        Forms\Components\Placeholder::make('total_amount')
                            ->label('Total Belanja')
                            ->content(fn ($get) => 'Rp ' . number_format($get('total_amount'), 0, ',', '.'))
                            ->columnSpan(1),
                            
                        Forms\Components\Placeholder::make('paid_amount')
                            ->label('Total Dibayar')
                            ->content(fn ($get) => 'Rp ' . number_format($get('paid_amount'), 0, ',', '.'))
                            ->columnSpan(1),
                            
                        Forms\Components\Placeholder::make('balance')
                            ->label('Kembalian/Sisa')
                            ->content(function ($get) {
                                $balance = $get('paid_amount') - $get('total_amount');
                                $formattedBalance = 'Rp ' . number_format(abs($balance), 0, ',', '.');
                                if ($balance > 0) {
                                    return "Kembalian: {$formattedBalance}";
                                } elseif ($balance < 0) {
                                    return "Sisa: {$formattedBalance}";
                                }
                                return "Lunas";
                            })
                            ->extraAttributes(function ($get) {
                                $balance = $get('paid_amount') - $get('total_amount');
                                $color = $balance >= 0 ? 'text-success-500' : 'text-danger-500';
                                return ['class' => 'text-lg font-bold ' . $color];
                            }),
                    ])
            ]);
    }

    private static function updateTotals($get, $set): void
    {
        // Hitung total amount dari details jika ada
        $details = $get('details') ?? [];
        $totalAmount = collect($details)->sum(fn ($item) => 
            isset($item['price_at_sale']) && isset($item['discount_per_item']) && isset($item['quantity']) ? 
            ($item['price_at_sale'] - ($item['discount_per_item'] ?? 0)) * $item['quantity'] : 0
        );
        
        // Hitung paid amount dari payments jika ada
        $payments = $get('payments') ?? [];
        $paidAmount = collect($payments)->sum(function ($item) {
            return $item['amount'] ?? 0;
        });
        
        $set('total_amount', $totalAmount);
        $set('paid_amount', $paidAmount);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cashier')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('IDR')
                    ->sortable()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('payments_sum_amount')
                    ->label('Total Paid')
                    ->money('IDR')
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->state(function (Transaction $record): string {
                        return $record->payment_status;
                    })
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'partial',
                        'danger' => 'unpaid',
                    ])
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'paid' => 'Paid',
                            'partial' => 'Partial',
                            'unpaid' => 'Unpaid',
                            default => $state,
                        };
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name'),
                    
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
                
                Tables\Filters\SelectFilter::make('payment_method')
                    ->relationship('payments.paymentMethod', 'name'),
                    
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Cashier'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    // Tables\Actions\Action::make('print_receipt')
                    //     ->icon('heroicon-o-printer')
                    //     ->url(fn ($record) => route('transactions.receipt', $record)),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\BulkAction::make('export')
                    //     ->icon('heroicon-o-arrow-down-tray')
                    //     ->action(fn ($records) => Excel::download(new TransactionsExport($records), 'transactions.xlsx')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DetailsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            // 'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}