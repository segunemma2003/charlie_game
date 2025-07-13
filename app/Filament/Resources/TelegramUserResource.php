<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramUserResource\Pages;
use App\Filament\Resources\TelegramUserResource\RelationManagers;
use App\Models\TelegramUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TelegramUserResource extends Resource
{
    protected static ?string $model = TelegramUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListTelegramUsers::route('/'),
            'create' => Pages\CreateTelegramUser::route('/create'),
            'view' => Pages\ViewTelegramUser::route('/{record}'),
            'edit' => Pages\EditTelegramUser::route('/{record}/edit'),
        ];
    }
}
