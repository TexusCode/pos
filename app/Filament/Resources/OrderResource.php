<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\OrderResource\Pages;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OrderResource\RelationManagers;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Управление продажами'; // Группа
    protected static ?int $navigationSort = 2; // Порядок сортировки

    protected static ?string $pluralModelLabel = 'Заказы';
    protected static ?string $modelLabel = 'Заказ';
    protected static ?string $recordTitleAttribute = 'order_number';
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Общая информация о заказе') // Явный заголовок
                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]) // Респонсивность
                    ->schema([
                        TextEntry::make('id')
                            ->label('№')
                            ->color('success') // Итоговая сумма зеленым
                            ->weight('bold'),
                        TextEntry::make('sub_total_amount')
                            ->label('Подытог')
                            ->numeric(2, ',', ' ') // Форматирование числа: 2 знака, запятая для десятичных, пробел для тысяч
                            ->suffix('с')
                            ->color('success') // Итоговая сумма зеленым
                            ->weight('bold') // Выделим жирным
                            ->icon('heroicon-o-banknotes'), // Иконка для суммы
                        TextEntry::make('discount_amount')
                            ->label('Скидка')
                            ->numeric(2, ',', ' ')
                            ->suffix('с')
                            ->color('danger') // Скидка красным
                            ->icon('heroicon-o-currency-dollar'), // Иконка для скидки

                        TextEntry::make('total_amount')
                            ->label('Итог')
                            ->numeric(2, ',', ' ') // Форматирование числа: 2 знака, запятая для десятичных, пробел для тысяч
                            ->suffix('с')
                            ->color('success') // Итоговая сумма зеленым
                            ->weight('bold') // Выделим жирным
                            ->icon('heroicon-o-banknotes'), // Иконка для суммы


                        TextEntry::make('payment_method')
                            ->label('Метод оплаты')
                            ->icon('heroicon-o-credit-card') // Иконка для метода оплаты
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'cash' => 'Наличные',
                                'card' => 'Карта',
                                'transfer' => 'Перевод',
                                default => $state,
                            }),
                        // Если 'payment_method' хранится на английском, а нужно отобразить на русском.
                        // Добавьте больше кейсов при необходимости.

                        TextEntry::make('created_at') // Добавим дату создания заказа, если она есть
                            ->label('Дата заказа')
                            ->dateTime('d.m.Y H:i')
                            ->icon('heroicon-o-calendar'),

                        // Информация о смене и кассире
                        TextEntry::make('shift.id')
                            ->label('Смена №')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-clipboard-document'), // Иконка для смены

                        TextEntry::make('shift.user.name')
                            ->label('Кассир')
                            ->icon('heroicon-o-user'), // Иконка для кассира
                    ]),

                Section::make('Детали товаров в заказе') // Явный заголовок для товаров
                    ->schema([
                        RepeatableEntry::make('orderItems')
                            ->label('Товары')
                            ->schema([
                                Grid::make(['default' => 3, 'sm' => 2, 'md' => 5]) // Адаптивная сетка для полей товара
                                    ->schema([
                                        TextEntry::make('product.name')
                                            ->label('Название')
                                            ->columnSpan(['default' => 2, 'sm' => 1])
                                            ->lineClamp(1)
                                            ->icon('heroicon-o-cube'), // Иконка для товара

                                        TextEntry::make('price')
                                            ->label('Цена')
                                            ->numeric(2, ',', ' ')
                                            ->suffix('с'), // Исправлено на 'с'

                                        TextEntry::make('quantity')
                                            ->label('Кол.')
                                            ->suffix(' шт.'), // Добавим ' шт.' для количества

                                        TextEntry::make('discount')
                                            ->label('Скидка')
                                            ->numeric(2, ',', ' ')
                                            ->suffix('с') // Исправлено на 'с'
                                            ->color('danger'),

                                        TextEntry::make('subtotal')
                                            ->label('Итог')
                                            ->numeric(2, ',', ' ')
                                            ->suffix('с') // Исправлено на 'с'
                                            ->weight('semibold'),
                                    ]),

                            ])
                            ->columns(1) // Важно, чтобы сам RepeatableEntry занимал одну колонку, а Grid внутри управлял расположением
                            ->contained(false), // Убираем внешнюю рамку для RepeatableEntry для более гладкого вида
                    ]),
            ]);

    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('customer_id')
                    ->numeric(),
                Forms\Components\TextInput::make('order_number')
                    ->required(),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('payment_method'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('shift_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('payment_status')
                    ->required(),
                Forms\Components\TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label("№"),
                Tables\Columns\TextColumn::make('shift_id')
                    ->label('Смена')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shift.user.name')
                    ->label('Кассир')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Скидка')
                    ->numeric()
                    ->suffix('c')

                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Итог')
                    ->suffix('c')

                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Метод оплата')
                    ->searchable(),

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
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Просмотр'), // Русская метка
                    // Tables\Actions\EditAction::make()->label('Редактировать'), // Русская метка
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');

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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
