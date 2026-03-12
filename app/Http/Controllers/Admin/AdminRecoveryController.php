<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeagueAttendance;
use App\Models\LeagueEvent;
use App\Models\LeagueMatch;
use App\Models\LeaguePlayer;
use App\Models\LeagueSeason;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRecoveryController extends Controller
{
    /**
     * Display the recovery management page.
     */
    public function index(Request $request): Response
    {
        $seasons = LeagueSeason::orderBy('created_at', 'desc')->get();
        $seasonId = $request->query('season_id');
        $currentEventId = $request->query('event_id');

        $events = [];
        if ($seasonId) {
            $events = LeagueEvent::where('season_id', $seasonId)
                ->where('event_type', 'liga')
                ->orderBy('event_date', 'asc')
                ->get();
        }

        $eligiblePlayers = [];
        if ($currentEventId && $seasonId) {
            $currentEvent = LeagueEvent::find($currentEventId);
            
            // 1. Get players present in the current event
            $presentPlayerIds = LeagueAttendance::where('event_id', $currentEventId)
                ->where('present', true)
                ->pluck('player_id');

            $players = LeaguePlayer::whereIn('id', $presentPlayerIds)->get();

            foreach ($players as $player) {
                // 2. Find missed dates (previous to current event date)
                $missedEvents = LeagueEvent::where('season_id', $seasonId)
                    ->where('event_type', 'liga')
                    ->where('event_date', '<', $currentEvent->event_date)
                    ->whereDoesntHave('attendance', function ($query) use ($player) {
                        $query->where('player_id', $player->id)->where('present', true);
                    })
                    ->get();

                if ($missedEvents->isNotEmpty()) {
                    $eligiblePlayers[] = [
                        'id' => $player->id,
                        'blader_name' => $player->blader_name,
                        'real_name' => $player->real_name,
                        'missed_count' => $missedEvents->count(),
                        'missed_dates' => $missedEvents->map(fn($e) => $e->name)->implode(', ')
                    ];
                }
            }
        }

        $recoveryMatches = [];
        if ($seasonId) {
            $recoveryMatches = LeagueMatch::with(['event', 'playerA', 'playerB', 'referee'])
                ->where('is_recovery', true)
                ->whereHas('event', fn($q) => $q->where('season_id', $seasonId))
                ->orderBy('concluded', 'asc')
                ->orderBy('id', 'desc')
                ->get();
        }

        return Inertia::render('Admin/Leagues/Recovery', [
            'seasons' => $seasons,
            'events' => $events,
            'eligiblePlayers' => $eligiblePlayers,
            'recoveryMatches' => $recoveryMatches,
            'filters' => $request->only(['season_id', 'event_id'])
        ]);
    }

    /**
     * Generate recovery matches for selected players.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'player_ids' => 'required|array|min:2',
            'player_ids.*' => 'exists:league_players,id',
            'event_id' => 'required|exists:league_events,id',
        ]);

        $playerIds = $validated['player_ids'];
        $eventId = $validated['event_id'];
        $generated = 0;

        // Generate round-robin among selected players
        for ($i = 0; $i < count($playerIds); $i++) {
            for ($j = $i + 1; $j < count($playerIds); $j++) {
                // Check if they already have a recovery match pending? 
                // Legacy didn't check, just generated.
                
                LeagueMatch::create([
                    'event_id' => $eventId,
                    'round_no' => 99, // Recovery round convention
                    'player_a_id' => $playerIds[$i],
                    'player_b_id' => $playerIds[$j],
                    'best_of' => 1,
                    'concluded' => false,
                    'is_recovery' => true,
                ]);
                $generated++;
            }
        }

        return back()->with('success', "Se han generado $generated matches de recuperación.");
    }
}
