<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ReturnOrder;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Infolists\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ReturnOrderResource\Pages;
use App\Filament\Resources\ReturnOrderResource\RelationManagers;
use Filament\Infolists\Components\Fieldset;

class ReturnOrderResource extends Resource
{
    protected static ?string $model = ReturnOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Управление продажами'; // Группа
    protected static ?int $navigationSort = 2; // Порядок сортировки

    protected static ?string $pluralModelLabel = 'Возвраты';
    protected static ?string $modelLabel = 'Возврат';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('shift_id')
                    ->required()
                    ->label('Смена')
                    ->numeric(),
                Forms\Components\TextInput::make('total')
                    ->label('Итог')

                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('discount')
                    ->required()
                    ->label('Скидка')

                    ->numeric()
                    ->default(0),
            ]);
    }
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Общая информация о возврате')
                    ->description('Основные данные по операции возврата товара.')
                    ->columns(['default' => 2, 'md' => 2, 'lg' => 3])
                    ->schema([
                        TextEntry::make('id')
                            ->label('Номер возврата')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-hashtag'), // Иконка для номера

                        // Предполагаем, что у ReturnOrder есть created_at
                        TextEntry::make('created_at')
                            ->label('Дата и время возврата')
                            ->dateTime('d.m.Y H:i')
                            ->icon('heroicon-o-calendar'),

                        TextEntry::make('shift.id') // Отношение к смене
                            ->label('Номер смены')
                            ->badge()
                            ->color('gray')
                            ->icon('heroicon-o-clipboard-document'),

                        TextEntry::make('shift.user.name') // Пользователь (кассир) из смены
                            ->label('Кассир')
                            ->icon('heroicon-o-user')
                            ->placeholder('Неизвестно'), // Если нет кассира

                        TextEntry::make('total')
                            ->label('Общая сумма возврата')
                            ->numeric(2, ',', ' ') // Две цифры после запятой, разделитель тысяч
                            ->suffix(' сом')
                            ->color('primary')
                            ->weight('bold')
                            ->size(TextEntry\TextEntrySize::Large) // Крупнее для итога
                            ->icon('heroicon-o-banknotes'),

                        TextEntry::make('discount')
                            ->label('Общая скидка по возврату')
                            ->numeric(2, ',', ' ')
                            ->suffix(' сом')
                            ->color('danger') // Скидка часто красным
                            ->icon('heroicon-o-minus-circle'), // Иконка скидки
                    ]),

                Section::make('Элементы возврата')
                    ->description('Список товаров, включенных в данный возврат.')
                    ->schema([
                        RepeatableEntry::make('return_order_items') // Отношение к элементам возврата
                            ->label('Список товаров')
                            ->columns(1) // Сам RepeatableEntry занимает 1 колонку
                            ->contained(false) // Убираем внешнюю рамку для RepeatableEntry
                            ->schema([
                                Fieldset::make('') // Рамка для каждого отдельного элемента возврата
                                    ->columns(['default' => 2, 'sm' => 2, 'md' => 4, 'lg' => 5]) // Адаптивная сетка для полей товара
                                    ->schema([
                                        TextEntry::make('product.name') // Название продукта из отношения
                                            ->label('Название товара')
                                            ->lineClamp(1)
                                            ->icon('heroicon-o-cube')
                                            ->weight('semibold')
                                            ->columnSpan(['default' => 1, 'md' => 2]), // Занимает больше места на средних/больших

                                        TextEntry::make('quantity')
                                            ->label('Количество')
                                            ->numeric(0, ',', ' ')
                                            ->suffix(' шт.')
                                            ->icon('heroicon-o-calculator'),

                                        TextEntry::make('price')
                                            ->label('Цена за ед.')
                                            ->numeric(2, ',', ' ')
                                            ->suffix(' сом')
                                            ->icon('heroicon-o-tag'),

                                        TextEntry::make('discount')
                                            ->label('Скидка на ед.')
                                            ->numeric(2, ',', ' ')
                                            ->suffix(' сом')
                                            ->color('danger'),

                                        TextEntry::make('subtotal')
                                            ->label('Итог по позиции')
                                            ->numeric(2, ',', ' ')
                                            ->suffix(' сом')
                                            ->color('primary')
                                            ->weight('medium')
                                            ->icon('heroicon-o-currency-dollar'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shift_id')
                    ->label('Смена')

                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Итог')

                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Скидка')

                    ->numeric()
                    ->sortable(),
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
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListReturnOrders::route('/'),
            'create' => Pages\CreateReturnOrder::route('/create'),
            'view' => Pages\ViewReturnOrder::route('/{record}'),
            'edit' => Pages\EditReturnOrder::route('/{record}/edit'),
        ];
    }
}
