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

class BattleFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Battle $battle)
    {
        $this->battle = $battle->load(['player1', 'player2', 'winner']);
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
        $winnerReward = $this->battle->total_pot * 0.5;
        $moonPotReward = $this->battle->total_pot * 0.5;

        return [
            'type' => 'battle_finished',
            'battle' => [
                'id' => $this->battle->id,
                'winner_id' => $this->battle->winner_id,
                'status' => $this->battle->status,
                'final_score' => $this->calculateFinalScore(),
            ],
            'rewards' => [
                'winner_charlie_points' => $winnerReward,
                'moon_pot_points' => $moonPotReward,
                'cards_lost' => $this->battle->is_risk_mode ? $this->getCardsLost() : [],
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    private function calculateFinalScore(): array
    {
        $player1Wins = $this->battle->battleCards()
            ->where('telegram_user_id', $this->battle->player1_id)
            ->where('result', 'win')
            ->count();

        $player2Wins = $this->battle->battleCards()
            ->where('telegram_user_id', $this->battle->player2_id)
            ->where('result', 'win')
            ->count();

        return [
            'player1_wins' => $player1Wins,
            'player2_wins' => $player2Wins,
            'total_rounds' => $this->battle->card_count,
        ];
    }

    private function getCardsLost(): array
    {
        if (!$this->battle->is_risk_mode) {
            return [];
        }

        $loserId = $this->battle->winner_id === $this->battle->player1_id
            ? $this->battle->player2_id
            : $this->battle->player1_id;

        return $this->battle->battleCards()
            ->where('telegram_user_id', $loserId)
            ->with('pnftCard')
            ->get()
            ->pluck('pnftCard.name')
            ->toArray();
    }
}
