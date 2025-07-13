<?php

namespace App\Filament\Resources\BattleResource\Pages;

use App\Filament\Resources\BattleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBattles extends ListRecords
{
    protected static string $resource = BattleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
