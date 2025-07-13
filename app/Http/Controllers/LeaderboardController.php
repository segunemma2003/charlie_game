<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelegramUser;
use App\Models\Battle;

class LeaderboardController extends Controller
{
    public function globalLeaderboard(Request $request)
    {
        $leaderboard = TelegramUser::orderBy('total_wins', 'desc')
            ->orderBy('charlie_points', 'desc')
            ->limit(100)
            ->get(['id', 'first_name', 'last_name', 'total_wins', 'total_losses', 'charlie_points']);

        return response()->json([
            'success' => true,
            'leaderboard' => $leaderboard
        ]);
    }

    public function weeklyLeaderboard(Request $request)
    {
        $weekAgo = now()->subWeek();

        $leaderboard = TelegramUser::withCount(['battles as weekly_wins' => function($query) use ($weekAgo) {
            $query->where('winner_id', 'telegram_users.id')
                  ->where('created_at', '>=', $weekAgo);
        }])->orderBy('weekly_wins', 'desc')
          ->limit(100)
          ->get();

        return response()->json([
            'success' => true,
            'weekly_leaderboard' => $leaderboard
        ]);
    }

    public function userRank(Request $request)
    {
        $user = $request->user();

        $rank = TelegramUser::where('total_wins', '>', $user->total_wins)
            ->orWhere(function($query) use ($user) {
                $query->where('total_wins', $user->total_wins)
                      ->where('charlie_points', '>', $user->charlie_points);
            })->count() + 1;

        return response()->json([
            'success' => true,
            'rank' => $rank,
            'total_players' => TelegramUser::count()
        ]);
    }
}
