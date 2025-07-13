<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\TelegramUser;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $update = $request->all();
        Log::info('Telegram webhook received:', $update);

        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }

        return response('OK', 200);
    }

    private function handleMessage($message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';

        switch ($text) {
            case '/start':
                $this->handleStart($message);
                break;
            case '/battle':
                $this->showBattleOptions($chatId);
                break;
            case '/profile':
                $this->showProfile($chatId);
                break;
        }
    }

    private function handleStart($message)
    {
        $telegramUser = TelegramUser::updateOrCreate(
            ['telegram_id' => $message['from']['id']],
            [
                'username' => $message['from']['username'] ?? null,
                'first_name' => $message['from']['first_name'],
                'last_name' => $message['from']['last_name'] ?? null,
            ]
        );

        // Send welcome message via Telegram API
        $this->sendMessage($message['chat']['id'], 'Welcome to Charlie Unicorn Battle Game!');
    }

    private function sendMessage($chatId, $text)
    {
        // Implement Telegram Bot API message sending
        // You'll need to use Telegram Bot API here
    }

    private function showBattleOptions($chatId)
    {
        $this->sendMessage($chatId, 'Choose your battle mode: PvP, PvE, or Tournament');
    }

    private function showProfile($chatId)
    {
        $user = TelegramUser::where('telegram_id', $chatId)->first();
        if ($user) {
            $message = "Profile:\nWins: {$user->total_wins}\nLosses: {$user->total_losses}\nCharlie Points: {$user->charlie_points}";
            $this->sendMessage($chatId, $message);
        }
    }
}
