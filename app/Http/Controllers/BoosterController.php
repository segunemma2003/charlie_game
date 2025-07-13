<?php

namespace App\Http\Controllers;

use App\Models\UserBooster;
use Illuminate\Http\Request;

class BoosterController extends Controller
{
    public function index(Request $request)
    {
        $boosters = $request->user()->boosters;

        return response()->json([
            'success' => true,
            'boosters' => $boosters
        ]);
    }

    public function purchase(Request $request)
    {
        $request->validate([
            'package' => 'required|in:10,100,1000,5000',
            'payment_method' => 'required|string'
        ]);

        $packages = [
            '10' => ['quantity' => 10, 'price' => 10],
            '100' => ['quantity' => 100, 'price' => 80],
            '1000' => ['quantity' => 1000, 'price' => 600],
            '5000' => ['quantity' => 5000, 'price' => 2000]
        ];

        $package = $packages[$request->package];

        // Here you would integrate with your payment processor
        // For now, we'll just create the boosters

        $userBooster = UserBooster::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'booster_type' => 'standard'
            ],
            [
                'quantity' => \DB::raw('quantity + ' . $package['quantity']),
                'power_multiplier' => 100
            ]
        );

        return response()->json([
            'success' => true,
            'boosters' => $userBooster,
            'message' => 'Boosters purchased successfully'
        ]);
    }

    public function use(Request $request)
    {
        $request->validate([
            'booster_type' => 'required|string',
            'quantity' => 'required|integer|min:1'
        ]);

        $userBooster = $request->user()->boosters()
            ->where('booster_type', $request->booster_type)
            ->first();

        if (!$userBooster || $userBooster->quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient boosters'
            ], 400);
        }

        $userBooster->decrement('quantity', $request->quantity);

        return response()->json([
            'success' => true,
            'message' => 'Boosters used successfully',
            'remaining' => $userBooster->refresh()->quantity
        ]);
    }
}
