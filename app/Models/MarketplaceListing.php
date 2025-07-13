<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'buyer_id',
        'item_type',
        'item_id',
        'price',
        'status',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function seller()
    {
        return $this->belongsTo(TelegramUser::class, 'seller_id');
    }

    public function buyer()
    {
        return $this->belongsTo(TelegramUser::class, 'buyer_id');
    }
}
