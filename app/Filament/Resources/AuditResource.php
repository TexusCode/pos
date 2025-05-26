<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditResource\Pages;
use App\Filament\Resources\AuditResource\RelationManagers;
use App\Models\Audit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Управление складом'; // Группа
    protected static ?int $navigationSort = 4; // Порядок сортировки

    protected static ?string $pluralModelLabel = 'Ревизии';
    protected static ?string $modelLabel = 'Ревизия';
    protected static ?string $recordTitleAttribute = 'name';
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
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'view' => Pages\ViewAudit::route('/{record}'),
            'edit' => Pages\EditAudit::route('/{record}/edit'),
        ];
    }
}
