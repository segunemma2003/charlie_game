<?php

namespace App\Http\Controllers;

use App\Models\Battle;
use App\Models\BattleCard;
use App\Events\BattleJoined;
use App\Events\BattleRoundStarted;
use App\Events\BattleRoundResult;
use App\Events\BattleFinished;
use App\Services\BattleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BattleWebSocketController extends Controller
{
    public function __construct(private BattleService $battleService)
    {
        $this->battleService = $battleService;
    }

    public function joinBattle(Request $request, Battle $battle)
    {
        $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'exists:pnft_cards,id'
        ]);

        try {
            DB::beginTransaction();

            $updatedBattle = $this->battleService->joinBattle(
                $battle,
                $request->user(),
                $request->card_ids
            );

            // Broadcast that battle was joined
            broadcast(new BattleJoined($updatedBattle));

            // Start first round automatically
            $this->startNextRound($updatedBattle, 1);

            DB::commit();

            return response()->json([
                'success' => true,
                'battle' => $updatedBattle,
                'message' => 'Joined battle and first round started'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to join battle: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function playRound(Request $request, Battle $battle)
    {
        $request->validate([
            'round_number' => 'required|integer|min:1',
            'boosters_used' => 'nullable|array',
            'boosters_used.*.type' => 'required|string',
            'boosters_used.*.multiplier' => 'required|numeric|min:1',
        ]);

        if ($battle->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Battle is not in progress'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $roundNumber = $request->round_number;

            // Get user's card for this round
            $userCard = BattleCard::where('battle_id', $battle->id)
                ->where('telegram_user_id', $request->user()->id)
                ->where('round_number', $roundNumber)
                ->first();

            if (!$userCard) {
                throw new \Exception('No card found for this round');
            }

            // Update user's card with boosters
            $userCard->update([
                'boosters_used' => $request->boosters_used
            ]);

            // Check if opponent has also played
            $opponentCard = BattleCard::where('battle_id', $battle->id)
                ->where('telegram_user_id', '!=', $request->user()->id)
                ->where('round_number', $roundNumber)
                ->first();

            $roundComplete = false;

            // If opponent has boosters, calculate round result
            if ($opponentCard && $opponentCard->boosters_used !== null) {
                $result = $this->battleService->calculateRoundResult($userCard, $opponentCard);

                // Update both cards with results
                $this->updateCardResults($userCard, $opponentCard, $result);

                // Broadcast round result
                broadcast(new BattleRoundResult($battle, $roundNumber, [
                    'winner_id' => $result['winner_id'],
                    'player1' => [
                        'card_id' => $userCard->telegram_user_id === $battle->player1_id ? $userCard->id : $opponentCard->id,
                        'power' => $result['player1_power'],
                        'damage_dealt' => $result['damage_dealt'][$battle->player1_id],
                    ],
                    'player2' => [
                        'card_id' => $userCard->telegram_user_id === $battle->player2_id ? $userCard->id : $opponentCard->id,
                        'power' => $result['player2_power'],
                        'damage_dealt' => $result['damage_dealt'][$battle->player2_id],
                    ],
                ]));

                $roundComplete = true;

                // Check if battle is finished
                $this->checkBattleCompletion($battle);

                // Start next round if battle continues
                if ($battle->fresh()->status === 'in_progress' && $roundNumber < $battle->card_count) {
                    $this->startNextRound($battle, $roundNumber + 1);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'round_complete' => $roundComplete,
                'waiting_for_opponent' => !$roundComplete,
                'message' => $roundComplete ? 'Round completed' : 'Waiting for opponent'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to play round: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    private function startNextRound(Battle $battle, int $roundNumber)
    {
        $player1Card = BattleCard::where('battle_id', $battle->id)
            ->where('telegram_user_id', $battle->player1_id)
            ->where('round_number', $roundNumber)
            ->with('pnftCard')
            ->first();

        $player2Card = BattleCard::where('battle_id', $battle->id)
            ->where('telegram_user_id', $battle->player2_id)
            ->where('round_number', $roundNumber)
            ->with('pnftCard')
            ->first();

        $roundCards = [
            'player1' => [
                'id' => $player1Card->id,
                'name' => $player1Card->pnftCard->name,
                'power_level' => $player1Card->pnftCard->power_level,
                'rarity' => $player1Card->pnftCard->rarity,
                'image_path' => $player1Card->pnftCard->image_path,
            ],
            'player2' => [
                'id' => $player2Card->id,
                'name' => $player2Card->pnftCard->name,
                'power_level' => $player2Card->pnftCard->power_level,
                'rarity' => $player2Card->pnftCard->rarity,
                'image_path' => $player2Card->pnftCard->image_path,
            ],
        ];

        broadcast(new BattleRoundStarted($battle, $roundNumber, $roundCards));
    }

    private function updateCardResults(BattleCard $card1, BattleCard $card2, array $result)
    {
        $card1->update([
            'result' => $result['winner_id'] === $card1->telegram_user_id ? 'win' : 'loss',
            'damage_dealt' => $result['damage_dealt'][$card1->telegram_user_id],
            'damage_received' => $result['damage_dealt'][$card2->telegram_user_id],
        ]);

        $card2->update([
            'result' => $result['winner_id'] === $card2->telegram_user_id ? 'win' : 'loss',
            'damage_dealt' => $result['damage_dealt'][$card2->telegram_user_id],
            'damage_received' => $result['damage_dealt'][$card1->telegram_user_id],
        ]);
    }

    private function checkBattleCompletion(Battle $battle)
    {
        $player1Wins = $battle->battleCards()
            ->where('telegram_user_id', $battle->player1_id)
            ->where('result', 'win')
            ->count();

        $player2Wins = $battle->battleCards()
            ->where('telegram_user_id', $battle->player2_id)
            ->where('result', 'win')
            ->count();

        $requiredWins = ceil($battle->card_count / 2);

        if ($player1Wins >= $requiredWins || $player2Wins >= $requiredWins) {
            $this->battleService->completeBattle($battle);
            broadcast(new BattleFinished($battle->fresh()));
        }
    }

    // Get battle state for reconnection
    public function getBattleState(Battle $battle)
    {
        $user = request()->user();

        if ($battle->player1_id !== $user->id && $battle->player2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $battleCards = $battle->battleCards()->with('pnftCard')->get();

        $currentRound = $battleCards->where('result', 'pending')->min('round_number') ?? $battle->card_count + 1;

        return response()->json([
            'success' => true,
            'battle' => [
                'id' => $battle->id,
                'status' => $battle->status,
                'current_round' => $currentRound,
                'total_rounds' => $battle->card_count,
                'battle_style' => $battle->battle_style,
                'is_risk_mode' => $battle->is_risk_mode,
                'total_pot' => $battle->total_pot,
                'winner_id' => $battle->winner_id,
            ],
            'rounds' => $battleCards->groupBy('round_number')->map(function ($roundCards, $roundNum) use ($battle) {
                $player1Card = $roundCards->where('telegram_user_id', $battle->player1_id)->first();
                $player2Card = $roundCards->where('telegram_user_id', $battle->player2_id)->first();

                return [
                    'round_number' => $roundNum,
                    'status' => $player1Card->result,
                    'player1' => [
                        'card' => $player1Card->pnftCard->only(['name', 'power_level', 'rarity', 'image_path']),
                        'boosters_used' => $player1Card->boosters_used,
                        'damage_dealt' => $player1Card->damage_dealt,
                        'result' => $player1Card->result,
                    ],
                    'player2' => [
                        'card' => $player2Card->pnftCard->only(['name', 'power_level', 'rarity', 'image_path']),
                        'boosters_used' => $player2Card->boosters_used,
                        'damage_dealt' => $player2Card->damage_dealt,
                        'result' => $player2Card->result,
                    ],
                ];
            })->values(),
        ]);
    }
}
