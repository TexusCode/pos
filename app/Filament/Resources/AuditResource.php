<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Audit;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\AuditResource\Pages;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AuditResource\RelationManagers;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Управление складом'; // Группа
    protected static ?int $navigationSort = 4; // Порядок сортировки

    protected static ?string $pluralModelLabel = 'Ревизии';
    protected static ?string $modelLabel = 'Ревизия';
    protected static ?string $recordTitleAttribute = 'name';
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Основная информация о ревизии')
                    ->description('Общие данные по проведенной инвентаризации.')
                    ->columns(['default' => 2, 'md' => 2, 'lg' => 3]) // Более гибкая респонсивность
                    ->schema([
                        TextEntry::make('name')
                            ->label('Название ревизии')
                            ->icon('heroicon-o-document-text')
                            ->weight('bold')
                            ->size(TextEntry\TextEntrySize::Large)->columnSpanFull(),

                        TextEntry::make('audit_date')
                            ->label('Дата проведения')
                            ->date('d.m.Y') // Только дата
                            ->icon('heroicon-o-calendar-days')
                            ->color('gray'),

                        TextEntry::make('user.name') // Отношение к пользователю, который создал ревизию
                            ->label('Создатель ревизии')
                            ->icon('heroicon-o-user')
                            ->color('info'),

                        TextEntry::make('status')
                            ->label('Статус')
                            ->badge() // Статус как бейдж
                            ->color(fn(string $state): string => match ($state) {
                                'open' => 'info',    // Открыта
                                'closed' => 'success', // Закрыта
                                'canceled' => 'danger', // Отменена (если есть такой статус)
                                default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'open' => 'Открыта',
                                'closed' => 'Закрыта',
                                'canceled' => 'Отменена',
                                default => $state,
                            })
                            ->icon(fn(string $state): string => match ($state) { // Иконка для статуса
                                'open' => 'heroicon-o-lock-open',
                                'closed' => 'heroicon-o-lock-closed',
                                'canceled' => 'heroicon-o-x-circle',
                                default => 'heroicon-o-question-mark-circle',
                            }),
                        // --- Новые поля для общих минусов ---
                        TextEntry::make('total_negative_items_count')
                            ->label('Позиций с недостачей')
                            ->numeric(0, ',', ' ')
                            ->suffix(' шт.')
                            ->color('danger')
                            ->weight('bold')
                            ->icon('heroicon-o-exclamation-triangle'),

                        TextEntry::make('total_negative_difference_sum')
                            ->label('Общая недостача')
                            ->numeric(0, ',', ' ') // Количество штук, а не сумма денег, поэтому 0 знаков после запятой
                            ->suffix(' шт.')
                            ->color('danger')
                            ->weight('bold')
                            ->icon('heroicon-o-arrow-trending-down'),
                        TextEntry::make('total_negative_value_sum')
                            ->label('Общая сумма недостачи')
                            ->numeric(2, ',', ' ') // Количество штук, а не сумма денег, поэтому 0 знаков после запятой
                            ->suffix('c')
                            ->color('danger')
                            ->weight('bold')
                            ->icon('heroicon-o-arrow-trending-down'),
                    ]),

                Section::make('Детали подсчета товаров')
                    ->description('Подробная информация о каждом товаре, участвовавшем в ревизии.')
                    ->schema([
                        RepeatableEntry::make('auditItems') // Отношение к элементам аудита
                            ->label('Список товаров в ревизии')
                            ->columns(1) // Важно, чтобы сам RepeatableEntry занимал 1 колонку, а Grid внутри управлял расположением
                            ->contained(false) // Убираем рамку вокруг каждого повторяющегося элемента
                            ->schema([
                                Fieldset::make('') // Пустой Fieldset для рамки вокруг каждого элемента аудита
                                    ->columns(['default' => 3, 'sm' => 2, 'md' => 4, 'lg' => 5]) // Респонсивная сетка для полей AuditItem
                                    ->schema([
                                        TextEntry::make('product.name') // Название продукта из отношения
                                            ->label('Названия')
                                            ->columnSpan(['default' => 2, 'sm' => 1])
                                            ->lineClamp(1)
                                            ->icon('heroicon-o-cube')
                                            ->weight('semibold'),

                                        TextEntry::make('old_quantity')
                                            ->label('Было')
                                            ->numeric(0, ',', ' ') // Количество без десятичных знаков
                                            ->suffix(' шт.')
                                            ->color('gray'),

                                        TextEntry::make('new_quantity')
                                            ->label('Стало')
                                            ->numeric(0, ',', ' ')
                                            ->suffix(' шт.')
                                            ->color('primary')
                                            ->weight('medium'),

                                        TextEntry::make('difference')
                                            ->label('Разница')
                                            ->numeric(0, ',', ' ')
                                            ->suffix(' шт.')
                                            ->color(fn(int $state): string => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                                            ->icon(fn(int $state): string => $state > 0 ? 'heroicon-o-arrow-trending-up' : ($state < 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-minus'))
                                            ->weight('bold'),

                                        TextEntry::make('user.name') // Пользователь, который изменил этот товар (если есть)
                                            ->label('Изменил')
                                            ->icon('heroicon-o-user-circle')
                                            ->placeholder('Неизвестно')
                                            ->color('info'),
                                    ]),
                            ]),
                    ]),

                // Если у вас есть некий общий итог по ревизии (например, общая разница в стоимости)
                // ViewEntry::make('total_difference_summary')
                //     ->view('filament.infolists.entries.audit-summary') // Создайте кастомное view для этого
                //     ->columnSpanFull(),
            ]);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('audit_date')
                    ->label('Дата ревизии') // Русская метка
                    ->required()
                    ->unique(ignoreRecord: true), // Добавил unique, если вы хотите одну ревизию на дату
                Forms\Components\TextInput::make('name')
                    ->label('Название ревизии') // Русская метка
                    ->maxLength(255) // Ограничение длины строки
                    ->nullable(), // Если название не всегда обязательно
                Forms\Components\Textarea::make('notes')
                    ->label('Примечания') // Русская метка
                    ->columnSpanFull(), // Занимает всю ширину колонки
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('audit_date')
                    ->label('Дата ревизии') // Русская метка
                    ->date()
                    ->sortable()
                    ->searchable(), // Добавил searchable для поиска по дате
                Tables\Columns\TextColumn::make('name')
                    ->label('Название ревизии') // Русская метка
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name') // Показывает имя пользователя, а не ID
                    ->label('Провел ревизию') // Русская метка
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
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'view' => Pages\ViewAudit::route('/{record}'),
            'edit' => Pages\EditAudit::route('/{record}/edit'),
        ];
    }
}
