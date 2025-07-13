<?php

namespace App\Filament\Resources\CryptoTransactionResource\Pages;

use App\Filament\Resources\CryptoTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCryptoTransaction extends CreateRecord
{
    protected static string $resource = CryptoTransactionResource::class;
}
