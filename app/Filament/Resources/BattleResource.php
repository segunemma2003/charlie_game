<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BattleResource\Pages;
use App\Filament\Resources\BattleResource\RelationManagers;
use App\Models\Battle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BattleResource extends Resource
{
    protected static ?string $model = Battle::class;

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
            'index' => Pages\ListBattles::route('/'),
            'create' => Pages\CreateBattle::route('/create'),
            'view' => Pages\ViewBattle::route('/{record}'),
            'edit' => Pages\EditBattle::route('/{record}/edit'),
        ];
    }
}
