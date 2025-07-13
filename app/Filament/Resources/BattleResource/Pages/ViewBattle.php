<?php

namespace App\Filament\Resources\BattleResource\Pages;

use App\Filament\Resources\BattleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBattle extends ViewRecord
{
    protected static string $resource = BattleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
