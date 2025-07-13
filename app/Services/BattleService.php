<?php

namespace App\Services;

use App\Models\Battle;
use App\Models\BattleCard;
use App\Models\PnftCard;
use App\Models\TelegramUser;
use App\Events\BattleStarted;
use App\Events\BattleRoundCompleted;
use App\Events\BattleCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BattleService
{
    public function createBattle(TelegramUser $player1, array $cardIds, array $battleData): Battle
    {
        return DB::transaction(function () use ($player1, $cardIds, $battleData) {
            // Validate and lock cards
            $cards = PnftCard::whereIn('id', $cardIds)
                ->where('telegram_user_id', $player1->id)
                ->where('is_locked', false)
                ->lockForUpdate()
                ->get();

            if ($cards->count() !== count($cardIds)) {
                throw new \Exception('Some cards are not available for battle');
            }

            $totalPot = $cards->sum('charlie_points');

            $battle = Battle::create([
                'player1_id' => $player1->id,
                'battle_type' => $battleData['battle_type'],
                'battle_style' => $battleData['battle_style'],
                'card_count' => count($cardIds),
                'total_pot' => $totalPot,
                'status' => 'pending',
                'is_risk_mode' => $battleData['is_risk_mode'] ?? false,
                'tournament_id' => $battleData['tournament_id'] ?? null,
            ]);

            // Create battle cards and lock them
            foreach ($cards as $index => $card) {
                BattleCard::create([
                    'battle_id' => $battle->id,
                    'pnft_card_id' => $card->id,
                    'telegram_user_id' => $player1->id,
                    'round_number' => $index + 1,
                ]);

                $card->update(['is_locked' => true]);
            }

            return $battle->load(['player1', 'battleCards.pnftCard']);
        });
    }

    public function joinBattle(Battle $battle, TelegramUser $player2, array $cardIds): Battle
    {
        return DB::transaction(function () use ($battle, $player2, $cardIds) {
            if ($battle->player2_id !== null) {
                throw new \Exception('Battle already has a second player');
            }

            $cards = PnftCard::whereIn('id', $cardIds)
                ->where('telegram_user_id', $player2->id)
                ->where('is_locked', false)
                ->lockForUpdate()
                ->get();

            if ($cards->count() !== $battle->card_count) {
                throw new \Exception("Must select exactly {$battle->card_count} cards");
            }

            $battle->update([
                'player2_id' => $player2->id,
                'status' => 'in_progress',
                'total_pot' => $battle->total_pot + $cards->sum('charlie_points'),
            ]);

            foreach ($cards as $index => $card) {
                BattleCard::create([
                    'battle_id' => $battle->id,
                    'pnft_card_id' => $card->id,
                    'telegram_user_id' => $player2->id,
                    'round_number' => $index + 1,
                ]);

                $card->update(['is_locked' => true]);
            }

            // Clear matchmaking cache
            Cache::tags(['matchmaking'])->flush();

            // Broadcast battle started
            broadcast(new BattleStarted($battle->load(['player1', 'player2'])));

            return $battle;
        });
    }

    public function calculateRoundResult(BattleCard $card1, BattleCard $card2): array
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

        // Determine winner with some randomness for ties
        if ($power1 > $power2) {
            $winner = $card1->telegram_user_id;
        } elseif ($power2 > $power1) {
            $winner = $card2->telegram_user_id;
        } else {
            $winner = collect([$card1->telegram_user_id, $card2->telegram_user_id])->random();
        }

        return [
            'winner_id' => $winner,
            'player1_power' => $power1,
            'player2_power' => $power2,
            'damage_dealt' => [$card1->telegram_user_id => $power1, $card2->telegram_user_id => $power2],
        ];
    }

    public function completeBattle(Battle $battle): void
    {
        $player1Wins = BattleCard::where('battle_id', $battle->id)
            ->where('telegram_user_id', $battle->player1_id)
            ->where('result', 'win')
            ->count();

        $player2Wins = BattleCard::where('battle_id', $battle->id)
            ->where('telegram_user_id', $battle->player2_id)
            ->where('result', 'win')
            ->count();

        $requiredWins = ceil($battle->card_count / 2);

        if ($player1Wins >= $requiredWins || $player2Wins >= $requiredWins) {
            DB::transaction(function () use ($battle, $player1Wins, $player2Wins) {
                $winnerId = $player1Wins >= $player2Wins ? $battle->player1_id : $battle->player2_id;
                $loserId = $winnerId === $battle->player1_id ? $battle->player2_id : $battle->player1_id;

                $battle->update([
                    'winner_id' => $winnerId,
                    'status' => 'completed',
                ]);

                // Distribute rewards
                $winnerShare = $battle->total_pot * 0.5;
                $moonPotShare = $battle->total_pot * 0.5;

                TelegramUser::find($winnerId)->increment('charlie_points', $winnerShare);
                TelegramUser::find($winnerId)->increment('total_wins');
                TelegramUser::find($winnerId)->increment('moon_pot_points', $moonPotShare);
                TelegramUser::find($loserId)->increment('total_losses');

                // Handle card unlocking/burning
                $this->handleBattleCards($battle, $winnerId);

                // Broadcast completion
                broadcast(new BattleCompleted($battle));
            });
        }
    }

    private function handleBattleCards(Battle $battle, int $winnerId): void
    {
        $battle->battleCards()->each(function ($battleCard) use ($battle, $winnerId) {
            if ($battle->is_risk_mode && $battleCard->telegram_user_id !== $winnerId) {
                // In risk mode, loser loses their cards permanently
                $battleCard->pnftCard()->delete();
            } else {
                $battleCard->pnftCard()->update(['is_locked' => false]);
            }
        });
    }
}
