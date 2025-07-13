<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NFTMintingTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'pnft_card_id',
        'telegram_user_id',
        'recipient_address',
        'network',
        'status',
        'transaction_hash',
        'token_id',
        'ipfs_image_hash',
        'ipfs_metadata_hash',
        'metadata_uri',
        'blockchain_confirmations',
        'gas_used',
        'blockchain_data',
        'metadata',
        'error_message',
    ];

    protected $casts = [
        'blockchain_data' => 'array',
        'metadata' => 'array',
        'token_id' => 'string',
        'blockchain_confirmations' => 'integer',
        'gas_used' => 'integer',
    ];

    public function pnftCard()
    {
        return $this->belongsTo(PnftCard::class);
    }

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }
}
