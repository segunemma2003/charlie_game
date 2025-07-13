<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'crypto_currency',
        'amount_crypto',
        'amount_usd',
        'payment_address',
        'user_wallet_address',
        'item_type',
        'item_data',
        'status',
        'confirmations',
        'blockchain_tx_hash',
        'expires_at'
    ];

    protected $casts = [
        'item_data' => 'array',
        'expires_at' => 'datetime',
        'amount_crypto' => 'decimal:8',
        'amount_usd' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
