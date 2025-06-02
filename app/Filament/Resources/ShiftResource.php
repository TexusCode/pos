<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Shift;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\ShiftResource\Pages;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ShiftResource\RelationManagers;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Операции'; // Группа
    protected static ?int $navigationSort = 0; // Порядок сортировки

    protected static ?string $pluralModelLabel = 'Смены';
    protected static ?string $modelLabel = 'Смена';
    protected static ?string $recordTitleAttribute = 'name'; // или 'start_time'

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Общая информация о смене') // Заголовок секции
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 3]) // Улучшенная респонсивность
                    ->schema([
                        TextEntry::make('id')
                            ->label('№')
                            ->badge(), // Можно сделать ID бейджем для выделения

                        TextEntry::make('user.name')
                            ->label('Кассир')
                            ->icon('heroicon-o-user'), // Добавим иконку для визуала

                        TextEntry::make('start_time')
                            ->label('Начало смены')
                            ->dateTime('d.m.Y H:i') // Форматируем дату и время
                            ->icon('heroicon-o-clock'),

                        TextEntry::make('end_time')
                            ->label('Окончание смены')
                            ->dateTime('d.m.Y H:i') // Форматируем дату и время
                            ->icon('heroicon-o-clock'),

                        // Денежные поля с суффиксом и числовым форматированием
                        TextEntry::make('sub_total')
                            ->label('Подытог')
                            ->numeric(2, ',', ' ') // Форматирование числа: 2 знака, запятая для десятичных, пробел для тысяч
                            ->suffix('c')
                            ->color('primary'), // Выделим цветом

                        TextEntry::make('discounts')
                            ->label('Скидка')
                            ->numeric(2, ',', ' ')
                            ->suffix('c')
                            ->color('danger'), // Скидка часто красным цветом

                        TextEntry::make('debts')
                            ->label('Долги')
                            ->numeric(2, ',', ' ')
                            ->suffix('c')
                            ->color('warning'), // Долги могут быть предупреждением

                        TextEntry::make('initial_cash')
                            ->label('Нач. касса')
                            ->numeric(2, ',', ' ')
                            ->suffix('c')
                            ->color('success'), // Начальная касса может быть зеленой

                        TextEntry::make('final_cash')
                            ->label('Кон. касса')
                            ->numeric(2, ',', ' ')
                            ->suffix('c')
                            ->color('success')
                            ->weight('bold'), // Выделим итоговую кассу жирным
                    ]),

                Section::make('Детали заказов') // Заголовок для раздела заказов
                    ->schema([
                        RepeatableEntry::make('orders')
                            ->label('')
                            ->schema([
                                // Используем Grid для контроля колонок внутри каждого заказа
                                Grid::make(['default' => 1, 'sm' => 2, 'md' => 4]) // Респонсивный Grid для полей заказа
                                    ->schema([
                                        TextEntry::make('id')
                                            ->label('Номер заказа')
                                            ->badge(), // Номер заказа также может быть бейджем

                                        TextEntry::make('discount_amount')
                                            ->label('Скидка')
                                            ->numeric(2, ',', ' ')
                                            ->suffix('c')
                                            ->color('danger'),

                                        TextEntry::make('total_amount')
                                            ->label('Итог')
                                            ->numeric(2, ',', ' ')
                                            ->suffix('c')
                                            ->color('primary')
                                            ->weight('bold'),

                                        TextEntry::make('payment_method')
                                            ->label('Метод оплаты')
                                            ->icon('heroicon-o-credit-card'), // Иконка для метода оплаты
                                    ]),

                                // Вложенная секция для товаров внутри заказа
                                Fieldset::make('Товары в заказе') // Используем Fieldset для визуального разделения
                                    ->schema([
                                        RepeatableEntry::make('orderItems')
                                            ->label('Товары')
                                            ->schema([
                                                Grid::make()->columns(5) // 5 колонок для товаров
                                                    ->schema([
                                                        TextEntry::make('product.name')
                                                            ->label('Название')
                                                            ->lineClamp(1), // Ограничение на одну строку

                                                        TextEntry::make('price')
                                                            ->label('Цена')
                                                            ->numeric(2, ',', ' ')
                                                            ->suffix('c'), // Изменил 'c' на 'c'

                                                        TextEntry::make('quantity')
                                                            ->label('Кол.'), // Сократил "Количество" до "Кол."
                                                        // Добавим суффикс для количества, если нужно, например, ' шт.'
                                                        // ->suffix(' шт.'),

                                                        TextEntry::make('discount')
                                                            ->label('Скидка')
                                                            ->numeric(2, ',', ' ')
                                                            ->suffix('c')
                                                            ->color('danger'),

                                                        TextEntry::make('subtotal')
                                                            ->label('Итог')
                                                            ->numeric(2, ',', ' ')
                                                            ->suffix('c')
                                                            ->weight('semibold'),
                                                    ])->columnSpanFull(),
                                            ])->columnSpanFull(), // Каждый RepeatableEntry занимает одну колонку, чтобы Grid внутри работал
                                    ]),
                            ])
                            ->contained(false), // Уберите контейнер для repeatable, чтобы он выглядел более гладко
                    ]),
            ]);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о смене')
                    ->description('Основные данные о начале, окончании и статусе смены.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название смены') // Русская метка
                            ->maxLength(255)
                            ->nullable()
                            ->helperText('Например: "Дневная смена 24.05.2025" или "Ночная смена".'),
                        Forms\Components\DateTimePicker::make('start_time')
                            ->label('Время начала смены') // Русская метка
                            ->required()
                            ->native(false) // Для улучшения стиля
                            ->seconds(false), // Убираем секунды для более чистого ввода
                        Forms\Components\DateTimePicker::make('end_time')
                            ->label('Время окончания смены') // Русская метка
                            ->nullable()
                            ->native(false)
                            ->seconds(false)
                            ->placeholder('Смена еще не закрыта'), // Подсказка для незавершенной смены
                        Forms\Components\Select::make('user_id')
                            ->label('Открыл пользователь') // Русская метка
                            ->relationship('user', 'name') // Связь с моделью User
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Статус смены') // Русская метка
                            ->options([
                                'open' => 'Открыта',
                                'closed' => 'Закрыта',
                            ])
                            ->required()
                            ->default('open')
                            ->native(false),
                    ])->columns(2), // Разделим эту секцию на 2 колонки

                Forms\Components\Section::make('Финансовые данные смены')
                    ->description('Учет наличных средств, скидок и долгов за смену.')
                    ->schema([
                        Forms\Components\TextInput::make('initial_cash')
                            ->label('Начальная наличность в кассе') // Русская метка
                            ->numeric()
                            ->required()
                            ->default(0.00)
                            ->suffix('c') // Валюта
                            ->inputMode('decimal'),
                        Forms\Components\TextInput::make('final_cash')
                            ->label('Конечная наличность в кассе') // Русская метка
                            ->numeric()
                            ->nullable()
                            ->suffix('c')
                            ->inputMode('decimal')
                            ->helperText('Заполняется при закрытии смены.'),
                        Forms\Components\TextInput::make('discounts') // Если это поле существует в миграции
                            ->label('Общая сумма скидок') // Русская метка
                            ->numeric()
                            ->default(0.00)
                            ->suffix('c')
                            ->nullable()
                            ->inputMode('decimal')
                            ->helperText('Общая сумма скидок за смену.'),
                        Forms\Components\TextInput::make('debts') // Если это поле существует в миграции
                            ->label('Общая сумма долгов') // Русская метка
                            ->numeric()
                            ->default(0.00)
                            ->suffix('c')
                            ->nullable()
                            ->inputMode('decimal')
                            ->helperText('Общая сумма заказов "в долг" за смену.'),
                    ])->columns(2), // Разделим эту секцию на 2 колонки
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('№'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название смены') // Русская метка
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Кассир') // Русская метка
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Начало смены') // Русская метка
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Окончание смены') // Русская метка
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Не закрыта') // Подсказка для незакрытой смены
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус') // Русская метка
                    ->badge() // Отображаем как бейдж
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'open' => 'Открыта',
                            'closed' => 'Закрыта',
                            default => $state, // Возвращаем исходное значение, если нет совпадения
                        };
                    })
                    ->colors([ // Цвета для бейджей
                        'open' => 'info',    // Открыта (синий/голубой)
                        'closed' => 'success', // Закрыта (зеленый)
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('initial_cash')
                    ->label('Нач. касса') // Русская метка
                    ->numeric(decimalPlaces: 2)
                    ->suffix('c') // Форматируем как валюту (замените KGS на вашу валюту)
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_cash')
                    ->label('Кон. касса') // Русская метка
                    ->suffix('c')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->placeholder('Н/Д') // Недоступно
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('discounts') // Если поле существует
                    ->label('Скидки') // Русская метка
                    ->suffix('c')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('debts') // Если поле существует
                    ->label('Долги') // Русская метка
                    ->suffix('c')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано') // Русская метка
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено') // Русская метка
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Фильтр по Статусу')
                    ->options([
                        'open' => 'Открыта',
                        'closed' => 'Закрыта',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Фильтр по Пользователю')
                    ->relationship('user', 'name')
                    ->preload()
                    ->searchable(),
                Tables\Filters\Filter::make('start_time')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('С даты')
                            ->placeholder(fn($state): string => 'До ' . now()->subYear()->format('M d, Y')),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('По дату')
                            ->placeholder(fn($state): string => 'До ' . now()->format('M d, Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_time', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_time', '<=', $date),
                            );
                    })
                    ->label('По дате открытия'), // Заголовок для фильтра по дате
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Просмотр'), // Русская метка
                    Tables\Actions\EditAction::make()->label('Редактировать'), // Русская метка

                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make()->label('Удалить выбранные'),
                ]),
            ])->defaultSort('created_at', 'desc');

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
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'view' => Pages\ViewShift::route('/{record}'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
