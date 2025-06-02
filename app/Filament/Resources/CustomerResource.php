<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CustomerResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CustomerResource\RelationManagers;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Управление продажами'; // Группа
    protected static ?int $navigationSort = 1; // Порядок сортировки

    protected static ?string $pluralModelLabel = 'Клиенты';
    protected static ?string $modelLabel = 'Клиент';
    protected static ?string $recordTitleAttribute = 'name';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label("Имя")
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->label("Номер телефон")
                    ->required(),
                Forms\Components\TextInput::make('debt')
                    ->label("Долги"),
                Forms\Components\TextInput::make('address')
                    ->label("Адрес"),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label("Имя")
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label("Номер телефон")
                    ->searchable(),
                Tables\Columns\TextColumn::make('debt')
                    ->suffix('c')
                    ->label("Долги"),
                Tables\Columns\TextColumn::make('address')
                    ->label("Адрес")
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
                    Tables\Actions\EditAction::make()->label('Редактировать'), // Русская метка

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
            'index' => Pages\ManageCustomers::route('/'),
        ];
    }
}
