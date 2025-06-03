<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenceResource\Pages;
use App\Filament\Resources\ExpenceResource\RelationManagers;
use App\Models\Expence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenceResource extends Resource
{
    protected static ?string $model = Expence::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Операции'; // Группа

    protected static ?string $pluralModelLabel = 'Расходы';
    protected static ?string $modelLabel = 'Расход';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('shift_id')
                    ->label('Смена')

                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total')
                    ->label('Итог')

                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('description')
                    ->label('Описание')

                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')

                    ->numeric(),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManageExpences::route('/'),
        ];
    }
}
