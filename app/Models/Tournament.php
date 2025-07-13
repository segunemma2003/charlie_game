<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'format',
        'entry_fee',
        'prize_pool',
        'max_participants',
        'start_time',
        'end_time',
        'status',
        'rules',
        'skill_level_required'
    ];

    protected $casts = [
        'rules' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    public function participants()
    {
        return $this->belongsToMany(TelegramUser::class, 'tournament_participants');
    }

    public function battles()
    {
        return $this->hasMany(Battle::class);
    }
}
