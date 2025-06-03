<?php

namespace App\Filament\Resources;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\Unit;
use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductResource\RelationManagers;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Управление продуктами';
    protected static ?int $navigationSort = 0;
    protected static ?string $pluralModelLabel = 'Продукты';
    protected static ?string $modelLabel = 'Продукт';
    protected static ?string $recordTitleAttribute = 'name';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make() // Группируем основные поля в колонки
                    ->schema([
                        Forms\Components\Section::make('Основная информация о продукте')
                            ->description('Базовые данные, такие как название, артикул и статус.')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Название продукта') // Русская метка
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true) // Обновляем slug при потере фокуса
                                    ->afterStateUpdated(fn(string $operation, $state, Forms\Set $set) => ($operation === 'create' || $operation === 'edit') ? $set('slug', Str::slug($state)) : null),
                                Forms\Components\TextInput::make('slug')
                                    ->label('SLUG (ЧПУ)') // Русская метка
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true), // SLUG должен быть уникальным
                                // ->helperText('Автоматически генерируется из названия, но можно изменить вручную.'),
                                Forms\Components\TextInput::make('sku')
                                    ->label('Артикул (SKU)') // Русская метка
                                    ->unique(ignoreRecord: true) // SKU должен быть уникальным
                                    ->maxLength(255)
                                    ->required()
                                    ->helperText('Уникальный идентификатор продукта.'),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Количество') // Русская метка
                                    ->maxLength(255)
                                    ->required()

                            ])->columns(2), // 2 колонки для этой секции

                        Forms\Components\Section::make('Ценообразование')
                            ->description('Установка цен покупки и продажи.')
                            ->schema([
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Цена покупки') // Русская метка
                                    ->numeric()
                                    ->suffix('сом') // Суффикс валюты
                                    ->nullable()
                                    ->default(0.00)
                                    ->inputMode('decimal'), // Указываем, что это десятичное число
                                Forms\Components\TextInput::make('selling_price')
                                    ->label('Цена продажи') // Русская метка
                                    ->required()
                                    ->numeric()
                                    ->suffix('сом') // Суффикс валюты
                                    ->default(0.00)
                                    ->inputMode('decimal'),
                            ])->columns(2),

                    ])->columnSpan(['lg' => 2]), // Эта группа занимает 2/3 ширины на больших экранах

                Forms\Components\Group::make() // Группируем вспомогательные поля и фото
                    ->schema([
                        Forms\Components\Section::make('Изображение')
                            ->description('Основное фото продукта.')
                            ->schema([
                                Forms\Components\FileUpload::make('photo')
                                    ->label('Фото продукта') // Русская метка
                                    ->image() // Только изображения
                                    ->nullable()
                                    ->directory('product-photos') // Куда загружать фото (в storage/app/public/product-photos)
                                    ->visibility('public') // Доступно по публичному URL
                                    ->disk('public') // Используем диск 'public'
                                    ->imageEditor() // Добавляем встроенный редактор изображений
                                    ->imageEditorAspectRatios([ // Можно настроить соотношение сторон для обрезки
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])
                                    ->panelAspectRatio('2:1')
                                    ->panelLayout('integrated'), // Интегрированный вид загрузчика
                            ]),

                        Forms\Components\Section::make('Связи')
                            ->description('Привязка к категории, поставщику и единице измерения.')
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->label('Категория') // Русская метка
                                    ->options(Category::all()->pluck('name', 'id'))
                                    ->searchable() // Добавляем поиск по категориям
                                    ->preload() // Предзагружаем все категории для выбора
                                    ->nullable()
                                    ->native(false),
                                Forms\Components\Select::make('supplier_id')
                                    ->label('Поставщик') // Русская метка
                                    ->options(Supplier::all()->pluck('name', 'id'))

                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->native(false),
                                Forms\Components\Select::make('unit_id')
                                    ->label('Единица измерения') // Русская метка
                                    ->options(Unit::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->native(false),
                                Forms\Components\Select::make('status')
                                    ->label('Статус продукта') // Русская метка
                                    ->options([
                                        'active' => 'Активный',
                                        'inactive' => 'Неактивный',
                                        'draft' => 'Черновик',
                                    ])
                                    ->required()
                                    ->default('active')
                                    ->native(false) // Для лучшего стиля в Filament
                                    ->columnSpan(1), // Чтобы занимал только одну колонку в своей секции
                            ]),
                    ])->columnSpan(['lg' => 1]), // Эта группа занимает 1/3 ширины на больших экранах
            ])->columns(3); // Разделяем всю форму на 3 колонки
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Фото') // Русская метка
                    ->square() // Квадратная форма для фото
                    ->defaultImageUrl(url('/images/no-image.jpg')) // Путь к изображению по умолчанию, если фото нет
                    ->toggleable(isToggledHiddenByDefault: true), // Показываем по умолчанию
                Tables\Columns\TextColumn::make('name')
                    ->label('Название продукта') // Русская метка
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Коль') // Русская метка
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('Артикул (SKU)') // Русская метка
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Цена покупки') // Русская метка
                    ->money('TJS') // Форматируем как валюту (замените KGS на вашу валюту)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Скрываем по умолчанию
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Цена продажи') // Русская метка
                    ->money('TJS') // Форматируем как валюту
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name') // Показываем название категории
                    ->label('Категория') // Русская метка
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.name') // Показываем название поставщика
                    ->label('Поставщик') // Русская метка
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Скрываем по умолчанию
                Tables\Columns\TextColumn::make('unit.symbol') // Показываем символ единицы измерения
                    ->label('Ед. изм.') // Русская метка
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус') // Русская метка
                    ->badge() // Отображаем как бейдж
                    ->colors([
                        'active' => 'success',
                        'inactive' => 'danger',
                        'draft' => 'warning',
                    ])
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Фильтр по Категории') // Русская метка для фильтра
                    ->relationship('category', 'name')
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Фильтр по Поставщику') // Русская метка для фильтра
                    ->relationship('supplier', 'name')
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Фильтр по Статусу') // Русская метка для фильтра
                    ->options([
                        'active' => 'Активный',
                        'inactive' => 'Неактивный',
                        'draft' => 'Черновик',
                    ]),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Товары с низким остатком (< 10 шт.)') // Русская метка для нового фильтра
                    ->query(fn(Builder $query): Builder => $query->where('quantity', '<', 10)),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Просмотр'), // Русская метка
                    Tables\Actions\EditAction::make()->label('Редактировать'), // Русская метка
                    Tables\Actions\DeleteAction::make()->label('Удалить'), // Русская метка
                ]),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Удалить выбранные'), // Русская метка
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
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
