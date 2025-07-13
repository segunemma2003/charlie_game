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

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});

// Public API Documentation
Route::prefix('docs')->group(function () {
    Route::get('/', [ApiDocumentationController::class, 'index']);
    Route::get('/openapi', [ApiDocumentationController::class, 'downloadOpenApi']);
});

// Authentication routes (no auth required)
Route::prefix('auth')->group(function () {
    Route::post('/telegram', [AuthController::class, 'telegramLogin']);
    Route::post('/verify-token', function (Request $request) {
        try {
            $user = $request->user();
            return response()->json([
                'success' => true,
                'user' => $user,
                'valid' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Invalid token'
            ], 401);
        }
    })->middleware('auth:sanctum');
});

// Telegram webhook (no auth required)
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

// Public data endpoints (no auth required)
Route::prefix('public')->group(function () {
    // Public leaderboards
    Route::get('/leaderboards/global', [LeaderboardController::class, 'globalLeaderboard']);
    Route::get('/leaderboards/weekly', [LeaderboardController::class, 'weeklyLeaderboard']);

    // Public tournament info
    Route::get('/tournaments', [TournamentController::class, 'index']);
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);

    // Public marketplace listings
    Route::get('/marketplace', [MarketplaceController::class, 'index']);

    // Game statistics
    Route::get('/stats', function () {
        return response()->json([
            'success' => true,
            'stats' => [
                'total_players' => \App\Models\TelegramUser::count(),
                'total_battles' => \App\Models\Battle::count(),
                'total_cards' => \App\Models\PnftCard::count(),
                'active_tournaments' => \App\Models\Tournament::where('status', 'active')->count(),
            ]
        ]);
    });
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {

    // User profile management
    Route::prefix('profile')->group(function () {
        Route::get('/', [AuthController::class, 'profile']);
        Route::put('/', [AuthController::class, 'updateProfile']);
        Route::get('/stats', function (Request $request) {
            $user = $request->user();
            return response()->json([
                'success' => true,
                'stats' => [
                    'total_cards' => $user->pnftCards()->count(),
                    'total_battles' => $user->battles()->count(),
                    'win_rate' => $user->total_wins + $user->total_losses > 0
                        ? round(($user->total_wins / ($user->total_wins + $user->total_losses)) * 100, 1)
                        : 0,
                    'rank' => \App\Models\TelegramUser::where('total_wins', '>', $user->total_wins)
                        ->orWhere(function($q) use ($user) {
                            $q->where('total_wins', $user->total_wins)
                              ->where('charlie_points', '>', $user->charlie_points);
                        })->count() + 1
                ]
            ]);
        });
    });

    // PNFT Card management
    Route::prefix('cards')->group(function () {
        Route::get('/', [PnftCardController::class, 'index']);
        Route::post('/', [PnftCardController::class, 'store']);
        Route::get('/{pnftCard}', [PnftCardController::class, 'show']);
        Route::put('/{pnftCard}', [PnftCardController::class, 'update']);
        Route::delete('/{pnftCard}', [PnftCardController::class, 'destroy']);

        // Card collection stats
        Route::get('/collection/stats', function (Request $request) {
            $user = $request->user();
            $cards = $user->pnftCards();

            return response()->json([
                'success' => true,
                'collection' => [
                    'total_cards' => $cards->count(),
                    'total_value' => $cards->sum('charlie_points'),
                    'rarity_breakdown' => $cards->selectRaw('rarity, COUNT(*) as count')
                        ->groupBy('rarity')
                        ->pluck('count', 'rarity'),
                    'average_power' => round($cards->avg('power_level'), 1),
                    'locked_cards' => $cards->where('is_locked', true)->count(),
                ]
            ]);
        });
    });

    // Battle system
    Route::prefix('battles')->group(function () {
        Route::get('/', [BattleController::class, 'index']);
        Route::post('/', [BattleController::class, 'createBattle']);
        Route::get('/{battle}', [BattleController::class, 'show']);
        Route::post('/{battle}/join', [BattleController::class, 'joinBattle']);
        Route::post('/{battle}/play', [BattleController::class, 'playRound']);

        // Battle history with pagination
        Route::get('/history/user', function (Request $request) {
            $battles = $request->user()->battles()
                ->with(['player1', 'player2', 'winner'])
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'battles' => $battles
            ]);
        });

        // Quick battle matching
        Route::post('/quick-match', function (Request $request) {
            $request->validate([
                'battle_style' => 'required|in:funny,hardcore',
                'card_count' => 'required|in:1,3,5,10,20,50'
            ]);

            // Find available battles or create new one
            $battle = \App\Models\Battle::where('status', 'pending')
                ->where('battle_style', $request->battle_style)
                ->where('card_count', $request->card_count)
                ->whereNull('player2_id')
                ->where('player1_id', '!=', $request->user()->id)
                ->first();

            return response()->json([
                'success' => true,
                'battle' => $battle,
                'found_match' => (bool) $battle
            ]);
        });
    });

    // AI Opponent battles
    Route::prefix('ai')->group(function () {
        Route::get('/opponents', [AiOpponentController::class, 'getAvailableOpponents']);
        Route::post('/battle', [AiOpponentController::class, 'battleAi']);
    });

    // Booster management
    Route::prefix('boosters')->group(function () {
        Route::get('/', [BoosterController::class, 'index']);
        Route::post('/purchase', [BoosterController::class, 'purchase']);
        Route::post('/use', [BoosterController::class, 'use']);
    });

    // Tournament participation
    Route::prefix('tournaments')->group(function () {
        Route::post('/{tournament}/join', [TournamentController::class, 'join']);
        Route::delete('/{tournament}/leave', [TournamentController::class, 'leave']);

        // User's tournament history
        Route::get('/my-tournaments', function (Request $request) {
            $tournaments = $request->user()->tournaments()
                ->with(['participants'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'tournaments' => $tournaments
            ]);
        });
    });

    // Marketplace operations
    Route::prefix('marketplace')->group(function () {
        Route::post('/', [MarketplaceController::class, 'store']);
        Route::post('/{listing}/purchase', [MarketplaceController::class, 'purchase']);
        Route::delete('/{listing}', [MarketplaceController::class, 'cancel']);

        // User's marketplace activity
        Route::get('/my-listings', function (Request $request) {
            $listings = \App\Models\MarketplaceListing::where('seller_id', $request->user()->id)
                ->with(['seller', 'buyer'])
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'listings' => $listings
            ]);
        });

        Route::get('/my-purchases', function (Request $request) {
            $purchases = \App\Models\MarketplaceListing::where('buyer_id', $request->user()->id)
                ->with(['seller', 'buyer'])
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'purchases' => $purchases
            ]);
        });
    });

    // Payment and crypto transactions
    Route::prefix('payments')->group(function () {
        Route::post('/crypto/create', [PaymentController::class, 'createCryptoPayment']);
        Route::post('/crypto/status', [PaymentController::class, 'checkPaymentStatus']);
        Route::post('/wallet/connect', [PaymentController::class, 'connectWallet']);

        // Transaction history
        Route::get('/history', function (Request $request) {
            $transactions = \App\Models\CryptoTransaction::where('telegram_user_id', $request->user()->id)
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'transactions' => $transactions
            ]);
        });
    });

    // File uploads
    Route::prefix('uploads')->group(function () {
        Route::post('/card-image', [FileUploadController::class, 'uploadCardImage']);
        Route::post('/tournament-banner', [FileUploadController::class, 'uploadTournamentBanner']);
        Route::post('/battle-animation', [FileUploadController::class, 'uploadBattleAnimation']);
        Route::delete('/file', [FileUploadController::class, 'deleteFile']);
    });

    // Leaderboards with user context
    Route::prefix('leaderboards')->group(function () {
        Route::get('/rank', [LeaderboardController::class, 'userRank']);
        Route::get('/around-me', function (Request $request) {
            $user = $request->user();
            $userRank = \App\Models\TelegramUser::where('total_wins', '>', $user->total_wins)
                ->orWhere(function($q) use ($user) {
                    $q->where('total_wins', $user->total_wins)
                      ->where('charlie_points', '>', $user->charlie_points);
                })->count() + 1;

            $around = \App\Models\TelegramUser::orderBy('total_wins', 'desc')
                ->orderBy('charlie_points', 'desc')
                ->offset(max(0, $userRank - 6))
                ->limit(11)
                ->get(['id', 'first_name', 'last_name', 'total_wins', 'total_losses', 'charlie_points']);

            return response()->json([
                'success' => true,
                'leaderboard' => $around,
                'user_rank' => $userRank
            ]);
        });
    });

    // Real-time notifications (WebSocket endpoints would go here)
    Route::prefix('notifications')->group(function () {
        Route::get('/', function (Request $request) {
            // Return user notifications
            return response()->json([
                'success' => true,
                'notifications' => []
            ]);
        });

        Route::post('/mark-read', function (Request $request) {
            $request->validate(['notification_ids' => 'required|array']);

            return response()->json([
                'success' => true,
                'message' => 'Notifications marked as read'
            ]);
        });
    });
});

// Catch-all route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'available_endpoints' => [
            'auth' => '/api/auth/*',
            'profile' => '/api/profile/*',
            'cards' => '/api/cards/*',
            'battles' => '/api/battles/*',
            'tournaments' => '/api/tournaments/*',
            'marketplace' => '/api/marketplace/*',
            'payments' => '/api/payments/*',
            'leaderboards' => '/api/leaderboards/*',
            'public' => '/api/public/*'
        ]
    ], 404);
});
