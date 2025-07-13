<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Battle;
use App\Models\PnftCard;

class AiOpponentController extends Controller
{
    public function getAvailableOpponents(Request $request)
    {
        $opponents = [
            [
                'id' => 'ai_beginner',
                'name' => 'Rainbow Rookie',
                'difficulty' => 'beginner',
                'power_level' => 80,
                'description' => 'A gentle unicorn perfect for practice'
            ],
            [
                'id' => 'ai_intermediate',
                'name' => 'Sparkle Warrior',
                'difficulty' => 'intermediate',
                'power_level' => 120,
                'description' => 'A seasoned fighter with tricks up its horn'
            ],
            [
                'id' => 'ai_advanced',
                'name' => 'Nightmare Destroyer',
                'difficulty' => 'advanced',
                'power_level' => 180,
                'description' => 'A fearsome opponent that shows no mercy'
            ]
        ];

        return response()->json([
            'success' => true,
            'opponents' => $opponents
        ]);
    }

    public function battleAi(Request $request)
    {
        $request->validate([
            'opponent_id' => 'required|string',
            'card_ids' => 'required|array',
            'battle_style' => 'required|in:funny,hardcore'
        ]);

        // Create AI battle logic here
        $cards = PnftCard::whereIn('id', $request->card_ids)
            ->where('telegram_user_id', $request->user()->id)
            ->get();

        if ($cards->count() !== count($request->card_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid cards selected'
            ], 400);
        }

        // Simulate AI battle
        $result = $this->simulateAiBattle($cards, $request->opponent_id);

        return response()->json([
            'success' => true,
            'battle_result' => $result
        ]);
    }

    private function simulateAiBattle($playerCards, $opponentId)
    {
        // Simple AI battle simulation
        $aiPowers = [
            'ai_beginner' => 80,
            'ai_intermediate' => 120,
            'ai_advanced' => 180
        ];

        $aiPower = $aiPowers[$opponentId] ?? 100;
        $playerPower = $playerCards->sum('power_level');

        $victory = $playerPower > $aiPower;

        return [
            'victory' => $victory,
            'player_power' => $playerPower,
            'ai_power' => $aiPower,
            'rewards' => $victory ? ['charlie_points' => 100] : []
        ];
    }
}
