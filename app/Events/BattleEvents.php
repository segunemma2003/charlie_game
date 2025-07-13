<?php

namespace App\Events;

use App\Models\Battle;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BattleStarted implements ShouldBroadcast
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
            'status' => $this->battle->status,
            'players' => [
                'player1' => $this->battle->player1->only(['id', 'first_name']),
                'player2' => $this->battle->player2?->only(['id', 'first_name']),
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}


