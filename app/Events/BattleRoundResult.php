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

class BattleRoundResult implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Battle $battle,
        public int $roundNumber,
        public array $result
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('battle.' . $this->battle->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'round_result',
            'battle_id' => $this->battle->id,
            'round_number' => $this->roundNumber,
            'result' => $this->result,
            'timestamp' => now()->toISOString(),
        ];
    }
}
