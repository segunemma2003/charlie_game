<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BattleCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'battle_id',
        'pnft_card_id',
        'telegram_user_id',
        'round_number',
        'boosters_used',
        'result',
        'damage_dealt',
        'damage_received'
    ];

    protected $casts = [
        'boosters_used' => 'array'
    ];

    public function battle()
    {
        return $this->belongsTo(Battle::class);
    }

    public function pnftCard()
    {
        return $this->belongsTo(PnftCard::class);
    }

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }
}
