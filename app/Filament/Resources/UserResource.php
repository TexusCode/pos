<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Администрирование'; // Группа
    protected static ?int $navigationSort = 0; // Порядок сортировки

    protected static ?string $pluralModelLabel = 'Сотрудники';
    protected static ?string $modelLabel = 'Сотрудник';
    protected static ?string $recordTitleAttribute = 'name';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Имя')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->label('Номер телефон')
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->required(),
                Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Админ',
                        'pos' => 'Продавец',
                        'audit' => 'Ревизёр',
                    ])
                    ->label('Должность')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Номер телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Должность')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Создано')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Обновлено')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
