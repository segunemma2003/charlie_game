<?php

namespace App\Jobs;

use App\Models\Battle;
use App\Models\BattleCard;
use App\Events\BattleUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBattleRound implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $battle;
    protected $roundNumber;

    public function __construct(Battle $battle, int $roundNumber)
    {
        $this->battle = $battle;
        $this->roundNumber = $roundNumber;
    }

    public function handle()
    {
        $player1Card = BattleCard::where('battle_id', $this->battle->id)
            ->where('user_id', $this->battle->player1_id)
            ->where('round_number', $this->roundNumber)
            ->first();

        $player2Card = BattleCard::where('battle_id', $this->battle->id)
            ->where('user_id', $this->battle->player2_id)
            ->where('round_number', $this->roundNumber)
            ->first();

        if ($player1Card && $player2Card) {
            // Calculate battle results
            $this->calculateBattleResult($player1Card, $player2Card);

            // Broadcast update
            broadcast(new BattleUpdated($this->battle->refresh()));
        }
    }

    private function calculateBattleResult(BattleCard $card1, BattleCard $card2)
    {
        $power1 = $card1->pnftCard->power_level;
        $power2 = $card2->pnftCard->power_level;

        // Apply boosters
        if ($card1->boosters_used) {
            foreach ($card1->boosters_used as $booster) {
                $power1 *= ($booster['multiplier'] / 100);
            }
        }

        if ($card2->boosters_used) {
            foreach ($card2->boosters_used as $booster) {
                $power2 *= ($booster['multiplier'] / 100);
            }
        }

        // Determine winner
        if ($power1 > $power2) {
            $card1->update(['result' => 'win', 'damage_dealt' => $power1, 'damage_received' => $power2]);
            $card2->update(['result' => 'loss', 'damage_dealt' => $power2, 'damage_received' => $power1]);
        } elseif ($power2 > $power1) {
            $card1->update(['result' => 'loss', 'damage_dealt' => $power1, 'damage_received' => $power2]);
            $card2->update(['result' => 'win', 'damage_dealt' => $power2, 'damage_received' => $power1]);
        } else {
            // Tie - random winner
            $winner = rand(0, 1);
            if ($winner === 0) {
                $card1->update(['result' => 'win', 'damage_dealt' => $power1, 'damage_received' => $power2]);
                $card2->update(['result' => 'loss', 'damage_dealt' => $power2, 'damage_received' => $power1]);
            } else {
                $card1->update(['result' => 'loss', 'damage_dealt' => $power1, 'damage_received' => $power2]);
                $card2->update(['result' => 'win', 'damage_dealt' => $power2, 'damage_received' => $power1]);
            }
        }
    }
}
