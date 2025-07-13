<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PnftCardController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\BoosterController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\AiOpponentController;
use App\Http\Controllers\ApiDocumentationController;

// Authentication routes
Route::post('/auth/telegram', [AuthController::class, 'telegramLogin']);

// Telegram webhook (no auth required)
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

Route::middleware(['auth:sanctum'])->group(function () {
    // User profile routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // PNFT Card routes
    Route::apiResource('cards', PnftCardController::class);

    // Battle routes
    Route::get('/battles', [BattleController::class, 'index']);
    Route::post('/battles', [BattleController::class, 'createBattle']);
    Route::post('/battles/{battle}/join', [BattleController::class, 'joinBattle']);
    Route::post('/battles/{battle}/play', [BattleController::class, 'playRound']);
    Route::get('/battles/{battle}', [BattleController::class, 'show']);

    // Booster routes
    Route::get('/boosters', [BoosterController::class, 'index']);
    Route::post('/boosters/purchase', [BoosterController::class, 'purchase']);
    Route::post('/boosters/use', [BoosterController::class, 'use']);

    // Tournament routes
    Route::get('/tournaments', [TournamentController::class, 'index']);
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);
    Route::post('/tournaments/{tournament}/join', [TournamentController::class, 'join']);
    Route::delete('/tournaments/{tournament}/leave', [TournamentController::class, 'leave']);

    // Marketplace routes
    Route::get('/marketplace', [MarketplaceController::class, 'index']);
    Route::post('/marketplace', [MarketplaceController::class, 'store']);
    Route::post('/marketplace/{listing}/purchase', [MarketplaceController::class, 'purchase']);
    Route::delete('/marketplace/{listing}', [MarketplaceController::class, 'cancel']);

    // Leaderboard routes
    Route::get('/leaderboards/global', [LeaderboardController::class, 'globalLeaderboard']);
    Route::get('/leaderboards/weekly', [LeaderboardController::class, 'weeklyLeaderboard']);
    Route::get('/leaderboards/rank', [LeaderboardController::class, 'userRank']);

    // AI Opponent routes
    Route::get('/ai/opponents', [AiOpponentController::class, 'getAvailableOpponents']);
    Route::post('/ai/battle', [AiOpponentController::class, 'battleAi']);

    // File upload routes
    Route::post('/upload/card-image', [FileUploadController::class, 'uploadCardImage']);
    Route::post('/upload/tournament-banner', [FileUploadController::class, 'uploadTournamentBanner']);
    Route::post('/upload/battle-animation', [FileUploadController::class, 'uploadBattleAnimation']);
    Route::delete('/upload/file', [FileUploadController::class, 'deleteFile']);
});

// Public routes (no authentication required)
Route::get('/docs', [ApiDocumentationController::class, 'index']);
Route::get('/docs/openapi', [ApiDocumentationController::class, 'downloadOpenApi']);
