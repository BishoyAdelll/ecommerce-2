<?php

namespace App\Filament\Resources;

use App\Enums\ProductStatusEnum;
use App\Enums\RolesEnum;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-s-queue-list';
    protected static SubNavigationPosition $subNavigationPosition = subNavigationPosition::End;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()->schema([

                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function(callable $set, $state,string $operation){
                            $set('slug',Str::slug($state));
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('department_id')
                        ->required()
                        ->relationship('department', 'name')
                        ->label(__('Department'))
                        ->reactive()
                        ->afterStateUpdated(function(callable $set){
                            $set('category_id',null);
                        }),
                    Forms\Components\Select::make('category_id')
                        ->relationship(
                            name: 'category',titleAttribute: 'name',modifyQueryUsing: function (Builder $query, callable $get) {
                                $department_id = $get('department_id');
                                if($department_id){
                                    $query->where('department_id', $department_id);
                                }
                            }
                        )
                    ->label(__('Category'))
                    ->preload()
                    ->searchable()
                    ->required(),
                    Forms\Components\RichEditor::make('description')
                    ->label(__('Description'))
                    ->required()
                    ->toolbarButtons([
                        'blockquote',
                        'bold',
                        'bulletList',
                        'h2',
                        'italic',
                        'link',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                        'table',

                    ])
                    ->columnSpan(2),
                    Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric(),
                    Forms\Components\TextInput::make('quantity')
                    ->integer(),
                    Forms\Components\Select::make('status')
                    ->options(ProductStatusEnum::labels())
                    ->default(ProductStatusEnum::Draft->value)
                    ->required()
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('images')
                    ->collection('images')
                    ->limit(1)
                    ->conversion('thumb')
                    ->label(__('Image')),
                TextColumn::make('title')
                ->words(10)
                ->sortable(),
                TextColumn::make('status')
                    ->badge()
                ->colors(ProductStatusEnum::colors()),
                TextColumn::make('department.name'),
                TextColumn::make('category.name'),
                TextColumn::make('created_at')
                    ->dateTime(),


            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                ->options(ProductStatusEnum::labels()),
                Tables\Filters\SelectFilter::make('department_id')
                ->relationship('department', 'name')
            ])
            ->actions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'images' =>Pages\ProductImages::route('/{record}/images'),
            'variation-types' =>Pages\ProductVariationTypes::route('/{record}/variation-types'),
            'variations' =>Pages\ProductVariations::route('/{record}/variations'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return
            $page->generateNavigationItems([
                Pages\EditProduct::class,
                Pages\ProductImages::class,
                Pages\ProductVariationTypes::class,
                Pages\ProductVariations::class,
            ]);

    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user && $user->hasRole(RolesEnum::Vendor);
    }
}
