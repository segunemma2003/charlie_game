<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class TelegramUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'charlie_points',
        'moon_pot_points',
        'total_wins',
        'total_losses',
        'skill_level',
        'character_attributes',
        'battle_style_preference',
    ];

    protected $casts = [
        'character_attributes' => 'array',
        'telegram_id' => 'string',
    ];

    public function pnftCards()
    {
        return $this->hasMany(PnftCard::class);
    }

    public function boosters()
    {
        return $this->hasMany(UserBooster::class);
    }

    public function battles()
    {
        return $this->hasMany(Battle::class, 'player1_id')
            ->orWhere('player2_id', $this->id);
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_participants');
    }
}
