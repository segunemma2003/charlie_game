<?php

namespace App\Filament\Resources\CryptoTransactionResource\Pages;

use App\Filament\Resources\CryptoTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCryptoTransaction extends EditRecord
{
    protected static string $resource = CryptoTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
