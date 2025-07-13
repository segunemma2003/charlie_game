<?php

namespace App\Filament\Resources\CryptoTransactionResource\Pages;

use App\Filament\Resources\CryptoTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListCryptoTransactions extends ListRecords
{
    protected static string $resource = CryptoTransactionResource::class;
}
