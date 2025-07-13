<?php

namespace App\Filament\Resources\BattleResource\Pages;

use App\Filament\Resources\BattleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBattle extends EditRecord
{
    protected static string $resource = BattleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
