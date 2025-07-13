<?php

namespace App\Events;

use App\Models\Battle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Battle $battle)
    {
        $this->battle = $battle->load(['player1', 'player2']);
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('battle.' . $this->battle->id),
            new Channel('user.' . $this->battle->player1_id),
            new Channel('user.' . $this->battle->player2_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'battle_joined',
            'battle' => [
                'id' => $this->battle->id,
                'status' => $this->battle->status,
                'battle_style' => $this->battle->battle_style,
                'card_count' => $this->battle->card_count,
                'total_pot' => $this->battle->total_pot,
                'is_risk_mode' => $this->battle->is_risk_mode,
                'players' => [
                    'player1' => $this->battle->player1->only(['id', 'first_name', 'skill_level']),
                    'player2' => $this->battle->player2?->only(['id', 'first_name', 'skill_level']),
                ],
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
