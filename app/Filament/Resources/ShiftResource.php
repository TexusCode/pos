<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Filament\Resources\ShiftResource\RelationManagers;
use App\Models\Shift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Операции'; // Группа
    protected static ?int $navigationSort = 0; // Порядок сортировки

    protected static ?string $pluralModelLabel = 'Смены';
    protected static ?string $modelLabel = 'Смена';
    protected static ?string $recordTitleAttribute = 'name'; // или 'start_time'
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
                            ->suffix('сом') // Валюта
                            ->inputMode('decimal'),
                        Forms\Components\TextInput::make('final_cash')
                            ->label('Конечная наличность в кассе') // Русская метка
                            ->numeric()
                            ->nullable()
                            ->suffix('сом')
                            ->inputMode('decimal')
                            ->helperText('Заполняется при закрытии смены.'),
                        Forms\Components\TextInput::make('discounts') // Если это поле существует в миграции
                            ->label('Общая сумма скидок') // Русская метка
                            ->numeric()
                            ->default(0.00)
                            ->suffix('сом')
                            ->nullable()
                            ->inputMode('decimal')
                            ->helperText('Общая сумма скидок за смену.'),
                        Forms\Components\TextInput::make('debts') // Если это поле существует в миграции
                            ->label('Общая сумма долгов') // Русская метка
                            ->numeric()
                            ->default(0.00)
                            ->suffix('сом')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Название смены') // Русская метка
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Кто открыл') // Русская метка
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
                    ->colors([ // Цвета для бейджей
                        'open' => 'info',    // Открыта (синий/голубой)
                        'closed' => 'success', // Закрыта (зеленый)
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('initial_cash')
                    ->label('Нач. касса') // Русская метка
                    ->money('KGS') // Форматируем как валюту (замените KGS на вашу валюту)
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_cash')
                    ->label('Кон. касса') // Русская метка
                    ->money('KGS')
                    ->sortable()
                    ->placeholder('Н/Д') // Недоступно
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('discounts') // Если поле существует
                    ->label('Скидки') // Русская метка
                    ->money('KGS')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('debts') // Если поле существует
                    ->label('Долги') // Русская метка
                    ->money('KGS')
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
                Tables\Actions\ViewAction::make()->label('Просмотр'),
                Tables\Actions\EditAction::make()->label('Редактировать'),
                // Tables\Actions\DeleteAction::make()->label('Удалить'), // Удаление смен может быть нежелательным
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make()->label('Удалить выбранные'),
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
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'view' => Pages\ViewShift::route('/{record}'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
