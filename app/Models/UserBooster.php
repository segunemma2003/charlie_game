<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBooster extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_user_id',
        'booster_type',
        'quantity',
        'power_multiplier'
    ];

    public function user()
    {
        return $this->belongsTo(TelegramUser::class);
    }
}
