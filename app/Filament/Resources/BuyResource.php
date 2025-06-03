<?php

namespace App\Filament\Resources;

use App\Models\Buy;
use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BuyResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BuyResource\RelationManagers;

class BuyResource extends Resource
{
    protected static ?string $model = Buy::class;

    protected static ?string $navigationGroup = 'Управление продуктами';
    protected static ?int $navigationSort = 5;
    protected static ?string $pluralModelLabel = 'Покупки';
    protected static ?string $modelLabel = 'Покупка';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('product_id')
                    ->label('Товар')
                    ->options(Product::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required() // Товар обязателен
                    ->placeholder('Выберите товар')
                    ->columnSpan(1),

                TextInput::make('quantity')
                    ->label('Количество')
                    ->numeric() // Только числа
                    ->minValue(1) // Количество должно быть не менее 1
                    ->required()
                    ->columnSpan(1), // Занимает 1 колонку
            ]);
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {

        if (isset($data['product_id'], $data['quantity'])) {
            $product = Product::find($data['product_id']);

            if ($product) {
                $product->quantity += $data['quantity'];
                $product->save();
            }
        }

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Название продукта') // Русская метка
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.sku')
                    ->label('Артикуль') // Русская метка
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Коль') // Русская метка
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBuys::route('/'),
        ];
    }
}
