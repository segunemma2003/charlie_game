<?php

namespace App\Filament\Resources\PnftCardResource\Pages;

use App\Filament\Resources\PnftCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPnftCard extends ViewRecord
{
    protected static string $resource = PnftCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
