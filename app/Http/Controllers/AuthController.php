<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    public function telegramLogin(Request $request)
    {
        $request->validate([
            'telegram_id' => 'required|string',
            'TelegramUsername' => 'nullable|string',
            'first_name' => 'required|string',
            'last_name' => 'nullable|string'
        ]);

        $TelegramUser = TelegramUser::updateOrCreate(
            ['telegram_id' => $request->telegram_id],
            [
                'TelegramUsername' => $request->TelegramUsername,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
            ]
        );

        $token = $TelegramUser->createToken('telegram-auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'TelegramUser' => $TelegramUser,
            'token' => $token
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'TelegramUser' => $request->TelegramUser()->load(['pnftCards', 'boosters'])
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'character_attributes' => 'nullable|array|max:16',
            'battle_style_preference' => 'in:funny,hardcore'
        ]);

        $TelegramUser = $request->TelegramUser();
        $TelegramUser->update($request->only(['character_attributes', 'battle_style_preference']));

        return response()->json([
            'success' => true,
            'TelegramUser' => $TelegramUser
        ]);
    }
}
