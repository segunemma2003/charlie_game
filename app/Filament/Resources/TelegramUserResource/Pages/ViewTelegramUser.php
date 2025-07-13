<?php

namespace App\Filament\Resources\TelegramUserResource\Pages;

use App\Filament\Resources\TelegramUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTelegramUser extends ViewRecord
{
    protected static string $resource = TelegramUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
