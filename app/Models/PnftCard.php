<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PnftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'image_url',
        'charlie_points',
        'attributes',
        'rarity',
        'is_locked',
        'power_level'
    ];

    protected $casts = [
        'attributes' => 'array',
        'is_locked' => 'boolean'
    ];

    public function telegramUser()
{
    return $this->belongsTo(TelegramUser::class, 'telegram_user_id');
}

    public function battleCards()
    {
        return $this->hasMany(BattleCard::class);
    }
}
