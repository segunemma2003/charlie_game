<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Battle;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('battle.{battleId}', function ($user, $battleId) {
    $battle = Battle::find($battleId);

    return $battle && (
        $battle->player1_id === $user->id ||
        $battle->player2_id === $user->id
    ) ? [
        'id' => $user->id,
        'name' => $user->first_name,
    ] : null;
});

Broadcast::channel('tournament.{tournamentId}', function ($user, $tournamentId) {
    return \App\Models\Tournament::find($tournamentId)
        ?->participants()
        ->where('telegram_user_id', $user->id)
        ->exists() ? [
            'id' => $user->id,
            'name' => $user->first_name,
        ] : null;
});
