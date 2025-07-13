<?php

namespace App\Http\Controllers;

use App\Models\Battle;
use App\Models\BattleCard;
use App\Models\PnftCard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\BattleService;

class BattleController extends Controller
{



    public function quickMatch(Request $request)
    {
        $request->validate([
            'battle_style' => 'required|in:funny,hardcore',
            'card_count' => 'required|in:1,3,5,10,20,50',
            'skill_level' => 'nullable|in:beginner,intermediate,advanced,expert'
        ]);

        $cacheKey = "quick_match:{$request->battle_style}:{$request->card_count}:{$request->skill_level}";

        $availableBattles = Cache::tags(['matchmaking'])->remember($cacheKey, 30, function () use ($request) {
            return Battle::where('status', 'pending')
                ->where('battle_style', $request->battle_style)
                ->where('card_count', $request->card_count)
                ->whereNull('player2_id')
                ->where('player1_id', '!=', $request->user()->id)
                ->when($request->skill_level, function ($query, $skillLevel) {
                    return $query->whereHas('player1', function ($q) use ($skillLevel) {
                        $q->where('skill_level', $skillLevel);
                    });
                })
                ->with('player1')
                ->limit(10)
                ->get();
        });

        $selectedBattle = $availableBattles->random();

        return response()->json([
            'success' => true,
            'battle' => $selectedBattle,
            'found_match' => $selectedBattle !== null,
            'queue_size' => $availableBattles->count()
        ]);
    }


    public function index(Request $request)
    {
        $battles = $request->user()->battles()
            ->with(['player1', 'player2', 'winner'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'battles' => $battles
        ]);
    }

    public function createBattle(Request $request)
    {
        $request->validate([
            'opponent_id' => 'nullable|exists:users,id',
            'battle_type' => 'required|in:pvp,pve,tournament',
            'battle_style' => 'required|in:funny,hardcore',
            'card_count' => 'required|in:1,3,5,10,20,50',
            'card_ids' => 'required|array',
            'card_ids.*' => 'exists:pnft_cards,id',
            'is_risk_mode' => 'boolean',
            'tournament_id' => 'nullable|exists:tournaments,id'
        ]);

        // Validate cards belong to user and calculate pot
        $cards = PnftCard::whereIn('id', $request->card_ids)
            ->where('user_id', $request->user()->id)
            ->where('is_locked', false)
            ->get();

        if ($cards->count() !== count($request->card_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or locked cards selected'
            ], 400);
        }

        $totalPot = $cards->sum('charlie_points');

        DB::beginTransaction();
        try {
            $battle = Battle::create([
                'player1_id' => $request->user()->id,
                'player2_id' => $request->opponent_id,
                'battle_type' => $request->battle_type,
                'battle_style' => $request->battle_style,
                'card_count' => $request->card_count,
                'total_pot' => $totalPot,
                'status' => 'pending',
                'is_risk_mode' => $request->is_risk_mode ?? false,
                'tournament_id' => $request->tournament_id
            ]);

            // Lock cards for battle
            foreach ($cards as $index => $card) {
                BattleCard::create([
                    'battle_id' => $battle->id,
                    'pnft_card_id' => $card->id,
                    'user_id' => $request->user()->id,
                    'round_number' => $index + 1,
                    'result' => 'pending'
                ]);

                $card->update(['is_locked' => true]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'battle' => $battle->load(['player1', 'player2', 'battleCards'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create battle'
            ], 500);
        }
    }

    public function joinBattle(Request $request, Battle $battle)
    {
        $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'exists:pnft_cards,id'
        ]);

        if ($battle->player2_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Battle already has a second player'
            ], 400);
        }

        $cards = PnftCard::whereIn('id', $request->card_ids)
            ->where('user_id', $request->user()->id)
            ->where('is_locked', false)
            ->get();

        if ($cards->count() !== $battle->card_count) {
            return response()->json([
                'success' => false,
                'message' => 'Must select exactly ' . $battle->card_count . ' cards'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $battle->update([
                'player2_id' => $request->user()->id,
                'status' => 'in_progress',
                'total_pot' => $battle->total_pot + $cards->sum('charlie_points')
            ]);

            foreach ($cards as $index => $card) {
                BattleCard::create([
                    'battle_id' => $battle->id,
                    'pnft_card_id' => $card->id,
                    'user_id' => $request->user()->id,
                    'round_number' => $index + 1,
                    'result' => 'pending'
                ]);

                $card->update(['is_locked' => true]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'battle' => $battle->load(['player1', 'player2', 'battleCards'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to join battle'
            ], 500);
        }
    }

    public function playRound(Request $request, Battle $battle)
    {
        $request->validate([
            'round_number' => 'required|integer|min:1',
            'boosters_used' => 'nullable|array'
        ]);

        if ($battle->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Battle is not in progress'
            ], 400);
        }

        $userCard = BattleCard::where('battle_id', $battle->id)
            ->where('user_id', $request->user()->id)
            ->where('round_number', $request->round_number)
            ->first();

        if (!$userCard) {
            return response()->json([
                'success' => false,
                'message' => 'No card found for this round'
            ], 400);
        }

        // Calculate battle result (simplified)
        $opponentCard = BattleCard::where('battle_id', $battle->id)
            ->where('user_id', '!=', $request->user()->id)
            ->where('round_number', $request->round_number)
            ->first();

        if ($opponentCard) {
            $userPower = $userCard->pnftCard->power_level;
            $opponentPower = $opponentCard->pnftCard->power_level;

            // Apply boosters
            if ($request->boosters_used) {
                foreach ($request->boosters_used as $booster) {
                    $userPower *= $booster['multiplier'];
                }
            }

            $result = $userPower > $opponentPower ? 'win' : 'loss';

            $userCard->update([
                'boosters_used' => $request->boosters_used,
                'result' => $result,
                'damage_dealt' => $userPower,
                'damage_received' => $opponentPower
            ]);

            // Check if battle is complete
            $this->checkBattleCompletion($battle);
        }

        return response()->json([
            'success' => true,
            'round_result' => $userCard->refresh()
        ]);
    }

    private function checkBattleCompletion(Battle $battle)
    {
        $player1Wins = BattleCard::where('battle_id', $battle->id)
            ->where('user_id', $battle->player1_id)
            ->where('result', 'win')
            ->count();

        $player2Wins = BattleCard::where('battle_id', $battle->id)
            ->where('user_id', $battle->player2_id)
            ->where('result', 'win')
            ->count();

        $requiredWins = ceil($battle->card_count / 2);

        if ($player1Wins >= $requiredWins || $player2Wins >= $requiredWins) {
            $winnerId = $player1Wins >= $requiredWins ? $battle->player1_id : $battle->player2_id;
            $loserId = $winnerId === $battle->player1_id ? $battle->player2_id : $battle->player1_id;

            DB::transaction(function () use ($battle, $winnerId, $loserId) {
                $battle->update([
                    'winner_id' => $winnerId,
                    'status' => 'completed'
                ]);

                // Distribute rewards
                $winnerShare = $battle->total_pot * 0.5;
                $moonPotShare = $battle->total_pot * 0.5;

                User::find($winnerId)->increment('charlie_points', $winnerShare);
                User::find($winnerId)->increment('total_wins');
                User::find($loserId)->increment('total_losses');

                // Add to global moon pot (you might want to store this separately)
                User::find($winnerId)->increment('moon_pot_points', $moonPotShare);

                // Unlock cards
                $battle->battleCards()->each(function ($battleCard) use ($battle, $winnerId) {
                    if ($battle->is_risk_mode && $battleCard->user_id !== $winnerId) {
                        // In risk mode, loser loses their cards
                        $battleCard->pnftCard()->delete();
                    } else {
                        $battleCard->pnftCard()->update(['is_locked' => false]);
                    }
                });
            });
        }
    }

    public function show(Battle $battle)
    {
        return response()->json([
            'success' => true,
            'battle' => $battle->load(['player1', 'player2', 'winner', 'battleCards.pnftCard'])
        ]);
    }
}
