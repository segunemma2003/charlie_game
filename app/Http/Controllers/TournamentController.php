<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function index(Request $request)
    {
        $query = Tournament::query();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $tournaments = $query->orderBy('start_time', 'asc')->paginate(20);

        return response()->json([
            'success' => true,
            'tournaments' => $tournaments
        ]);
    }

    public function show(Tournament $tournament)
    {
        return response()->json([
            'success' => true,
            'tournament' => $tournament->load(['participants', 'battles'])
        ]);
    }

    public function join(Request $request, Tournament $tournament)
    {
        if ($tournament->status !== 'upcoming') {
            return response()->json([
                'success' => false,
                'message' => 'Tournament is not accepting participants'
            ], 400);
        }

        if ($tournament->participants()->count() >= $tournament->max_participants) {
            return response()->json([
                'success' => false,
                'message' => 'Tournament is full'
            ], 400);
        }

        if ($tournament->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Already registered for this tournament'
            ], 400);
        }

        // Check entry fee
        if ($tournament->entry_fee > 0) {
            if ($request->user()->charlie_points < $tournament->entry_fee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient Charlie Points'
                ], 400);
            }

            $request->user()->decrement('charlie_points', $tournament->entry_fee);
        }

        $tournament->participants()->attach($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Successfully joined tournament'
        ]);
    }

    public function leave(Request $request, Tournament $tournament)
    {
        if ($tournament->status !== 'upcoming') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot leave tournament after it has started'
            ], 400);
        }

        $tournament->participants()->detach($request->user()->id);

        // Refund entry fee if applicable
        if ($tournament->entry_fee > 0) {
            $request->user()->increment('charlie_points', $tournament->entry_fee);
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully left tournament'
        ]);
    }
}
