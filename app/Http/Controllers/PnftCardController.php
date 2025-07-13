<?php

namespace App\Http\Controllers;

use App\Models\PnftCard;
use Illuminate\Http\Request;

class PnftCardController extends Controller
{
    public function index(Request $request)
    {
        $cards = $request->TelegramUser()->pnftCards()->paginate(20);

        return response()->json([
            'success' => true,
            'cards' => $cards
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image_url' => 'required|url',
            'charlie_points' => 'required|integer|min:1',
            'attributes' => 'required|array|max:16',
            'rarity' => 'required|in:common,uncommon,rare,epic,legendary'
        ]);

        $card = $request->TelegramUser()->pnftCards()->create($request->all());

        return response()->json([
            'success' => true,
            'card' => $card
        ], 201);
    }

    public function show(PnftCard $pnftCard)
    {
        $this->authorize('view', $pnftCard);

        return response()->json([
            'success' => true,
            'card' => $pnftCard
        ]);
    }

    public function update(Request $request, PnftCard $pnftCard)
    {
        $this->authorize('update', $pnftCard);

        $request->validate([
            'is_locked' => 'boolean'
        ]);

        $pnftCard->update($request->only(['is_locked']));

        return response()->json([
            'success' => true,
            'card' => $pnftCard
        ]);
    }

    public function destroy(PnftCard $pnftCard)
    {
        $this->authorize('delete', $pnftCard);

        $pnftCard->delete();

        return response()->json([
            'success' => true,
            'message' => 'Card deleted successfully'
        ]);
    }
}
