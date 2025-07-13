<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Battle;

class BattleCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Battle $battle)
    {
        //
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
            'battle_id' => $this->battle->id,
            'winner_id' => $this->battle->winner_id,
            'status' => $this->battle->status,
            'rewards' => [
                'winner_points' => $this->battle->total_pot * 0.5,
                'moon_pot_points' => $this->battle->total_pot * 0.5,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
