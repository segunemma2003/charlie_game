<?php

namespace App\Filament\Resources\PnftCardResource\Pages;

use App\Filament\Resources\PnftCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPnftCard extends EditRecord
{
    protected static string $resource = PnftCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
