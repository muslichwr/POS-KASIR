<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Collection;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;


class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Katalog';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Category Information')
                    ->columns(1)
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Category Image')
                            ->image()
                            ->directory('categories')
                            ->maxSize(2048)
                            ->columnSpanFull('full'),

                            
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Str::slug($state));
                            }),
                            
                            Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->hidden() // Disembunyikan dari tampilan form
                            ->disabled(fn (?Category $record) => $record !== null) // Nonaktif saat edit
                            ->dehydrated(false) // Tidak dikirim ke database jika tidak ada perubahan
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                            
                        Forms\Components\Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->optionsLimit(20)
                            ->helperText('Leave empty to create root category')
                            ->hintAction(
                                Forms\Components\Actions\Action::make('createParentCategory')
                                    ->label('New Parent')
                                    ->icon('heroicon-m-plus')
                                    ->form([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->action(function (array $data) {
                                        $category = Category::create($data);
                                        return $category->id;
                                    })
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->circular(),
                    // ->defaultImageUrl(url('/images/default-category.png')),
                    
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Category $record): string => $record->slug),
                    
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent Category')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->placeholder('Root Category'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
            Tables\Filters\SelectFilter::make('parent_id')
                ->label('Filter by Parent')
                ->options(function () {
                    return Category::query()
                        ->with('parent')
                        ->get()
                        ->mapWithKeys(function (Category $category) {
                            $prefix = $category->parent ? "{$category->parent->name} → " : '';
                            return [$category->id => $prefix . $category->name];
                        })
                        ->prepend('Root Categories', 'root');
                })
                ->query(function (Builder $query, array $data) {
                    return $query->when(
                        $data['value'],
                        fn(Builder $query, $value) => $value === 'root'
                            ? $query->whereNull('parent_id')
                            : $query->where('parent_id', $value)
                    );
                })
                ->indicator(function (array $data) {
                    if (!$data['value']) {
                        return null;
                    }
                    
                    return $data['value'] === 'root'
                        ? 'Root Categories'
                        : 'Parent: ' . Category::find($data['value'])?->name;
                })
                ->searchable()
                ->preload(),
            
            Tables\Filters\TernaryFilter::make('has_children')
                ->label('Has Subcategories')
                ->placeholder('All')
                ->trueLabel('With Subcategories')
                ->falseLabel('Without Subcategories')
                ->queries(
                    true: fn(Builder $query) => $query->whereHas('children'),
                    false: fn(Builder $query) => $query->whereDoesntHave('children'),
                ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function (Category $record) {
                            // Automatically make child categories root before deletion
                            $record->children()->update(['parent_id' => null]);
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->children()->update(['parent_id' => null]);
                            });
                        }),
                ]),
            ]);
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
