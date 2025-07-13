<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Battle extends Model
{
    use HasFactory;

    protected $fillable = [
        'player1_id',
        'player2_id',
        'winner_id',
        'battle_type',
        'battle_style',
        'card_count',
        'total_pot',
        'status',
        'round_results',
        'is_risk_mode',
        'tournament_id'
    ];

    protected $casts = [
        'round_results' => 'array',
        'is_risk_mode' => 'boolean'
    ];

    public function player1()
{
    return $this->belongsTo(TelegramUser::class, 'player1_id');
}

public function player2()
{
    return $this->belongsTo(TelegramUser::class, 'player2_id');
}

public function winner()
{
    return $this->belongsTo(TelegramUser::class, 'winner_id');
}

    public function battleCards()
    {
        return $this->hasMany(BattleCard::class);
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }
}
