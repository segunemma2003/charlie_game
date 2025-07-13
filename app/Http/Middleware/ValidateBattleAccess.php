<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Battle;

class ValidateBattleAccess
{
    public function handle(Request $request, Closure $next)
    {
        $battle = $request->route('battle');
        $user = $request->user();

        if (!$battle instanceof Battle) {
            return response()->json([
                'success' => false,
                'message' => 'Battle not found'
            ], 404);
        }

        // Check if user is participant in the battle
        if ($battle->player1_id !== $user->id && $battle->player2_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this battle'
            ], 403);
        }

        return $next($request);
    }
}
