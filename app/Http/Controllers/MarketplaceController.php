<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceListing;
use App\Models\PnftCard;
use App\Models\UserBooster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketplaceListing::where('status', 'active')
            ->where('expires_at', '>', now());

        if ($request->item_type) {
            $query->where('item_type', $request->item_type);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        $listings = $query->with(['seller'])->paginate(20);

        return response()->json([
            'success' => true,
            'listings' => $listings
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_type' => 'required|in:pnft_card,booster,attribute',
            'item_id' => 'required|integer',
            'price' => 'required|integer|min:1',
            'duration_hours' => 'required|integer|min:1|max:168'
        ]);

        // Validate ownership
        if ($request->item_type === 'pnft_card') {
            $item = PnftCard::where('id', $request->item_id)
                ->where('user_id', $request->user()->id)
                ->where('is_locked', false)
                ->first();
        } elseif ($request->item_type === 'booster') {
            $item = UserBooster::where('id', $request->item_id)
                ->where('user_id', $request->user()->id)
                ->first();
        }

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found or not available for sale'
            ], 400);
        }

        $listing = MarketplaceListing::create([
            'seller_id' => $request->user()->id,
            'item_type' => $request->item_type,
            'item_id' => $request->item_id,
            'price' => $request->price,
            'status' => 'active',
            'expires_at' => now()->addHours($request->duration_hours)
        ]);

        // Lock the item
        if ($request->item_type === 'pnft_card') {
            $item->update(['is_locked' => true]);
        }

        return response()->json([
            'success' => true,
            'listing' => $listing,
            'message' => 'Item listed successfully'
        ], 201);
    }

    public function purchase(Request $request, MarketplaceListing $listing)
    {
        if ($listing->status !== 'active' || $listing->expires_at <= now()) {
            return response()->json([
                'success' => false,
                'message' => 'Listing is no longer available'
            ], 400);
        }

        if ($listing->seller_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot purchase your own listing'
            ], 400);
        }

        if ($request->user()->charlie_points < $listing->price) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient Charlie Points'
            ], 400);
        }

        DB::transaction(function () use ($request, $listing) {
            // Transfer payment
            $request->user()->decrement('charlie_points', $listing->price);
            $listing->seller->increment('charlie_points', $listing->price);

            // Transfer item
            if ($listing->item_type === 'pnft_card') {
                PnftCard::where('id', $listing->item_id)
                    ->update([
                        'user_id' => $request->user()->id,
                        'is_locked' => false
                    ]);
            } elseif ($listing->item_type === 'booster') {
                $booster = UserBooster::find($listing->item_id);
                UserBooster::updateOrCreate(
                    [
                        'user_id' => $request->user()->id,
                        'booster_type' => $booster->booster_type
                    ],
                    [
                        'quantity' => DB::raw('quantity + ' . $booster->quantity),
                        'power_multiplier' => $booster->power_multiplier
                    ]
                );
                $booster->delete();
            }

            // Update listing
            $listing->update([
                'buyer_id' => $request->user()->id,
                'status' => 'sold'
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Purchase completed successfully'
        ]);
    }

    public function cancel(Request $request, MarketplaceListing $listing)
    {
        if ($listing->seller_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to cancel this listing'
            ], 403);
        }

        if ($listing->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Listing cannot be cancelled'
            ], 400);
        }

        // Unlock the item
        if ($listing->item_type === 'pnft_card') {
            PnftCard::where('id', $listing->item_id)
                ->update(['is_locked' => false]);
        }

        $listing->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Listing cancelled successfully'
        ]);
    }
}
