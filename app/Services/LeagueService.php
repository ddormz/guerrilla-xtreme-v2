<?php

namespace App\Services;

use App\Models\LeagueSeason;
use App\Models\LeaguePlayer;
use App\Models\LeaguePoints;
use App\Models\LeagueMatch;
use App\Enums\SeasonStatus;
use Illuminate\Support\Facades\DB;

class LeagueService
{
    /**
     * Get the standings for a specific season.
     */
    public function getStandings(LeagueSeason $season)
    {
        return LeaguePoints::where('season_id', $season->id)
            ->with('player')
            ->orderByDesc('points')
            ->orderByDesc('wins')
            ->orderByDesc('xtremes')
            ->get();
    }

    /**
     * Recalculate points for all players in a season based on match results.
     * SOLID: SRP - Handles points aggregation logic.
     */
    public function recalculateSeasonPoints(LeagueSeason $season): void
    {
        DB::transaction(function () use ($season) {
            // Reset all points for this season
            LeaguePoints::where('season_id', $season->id)->update([
                'points' => 0, 'wins' => 0, 'losses' => 0,
                'xtremes' => 0, 'spins' => 0, 'overs' => 0, 'bursts' => 0
            ]);

            // Get all concluded matches
            $matches = LeagueMatch::where('event_id', function($query) use ($season) {
                $query->select('id')->from('league_events')->where('season_id', $season->id);
            })->where('concluded', true)->get();

            foreach ($matches as $match) {
                $this->applyMatchResult($season->id, $match);
            }
        });
    }

    /**
     * Apply a single match result to the standings.
     */
    private function applyMatchResult(int $seasonId, LeagueMatch $match): void
    {
        if (!$match->player_a_id || !$match->player_b_id) return;

        // Player A
        $this->updatePlayerStats($seasonId, $match->player_a_id, [
            'win' => $match->winner_id === $match->player_a_id,
            'xtremes' => $match->xtreme_a,
            'spins' => $match->spin_a,
            'overs' => $match->over_a,
            'bursts' => $match->burst_a,
        ]);

        // Player B
        $this->updatePlayerStats($seasonId, $match->player_b_id, [
            'win' => $match->winner_id === $match->player_b_id,
            'xtremes' => $match->xtreme_b,
            'spins' => $match->spin_b,
            'overs' => $match->over_b,
            'bursts' => $match->burst_b,
        ]);
    }

    private function updatePlayerStats(int $seasonId, int $playerId, array $stats): void
    {
        $pointsRecord = LeaguePoints::firstOrCreate(
            ['season_id' => $seasonId, 'player_id' => $playerId]
        );

        $pointsRecord->increment('wins', $stats['win'] ? 1 : 0);
        $pointsRecord->increment('losses', $stats['win'] ? 0 : 1);
        $pointsRecord->increment('xtremes', $stats['xtremes']);
        $pointsRecord->increment('spins', $stats['spins']);
        $pointsRecord->increment('overs', $stats['overs']);
        $pointsRecord->increment('bursts', $stats['bursts']);

        // Calculate total points based on GX rules
        // (This logic can be injected via a Strategy pattern if rules change)
        $totalPoints = ($pointsRecord->wins * 3) + // 3 points per win
                       ($pointsRecord->xtremes * 1) + // Bonus for xtremes? Adjust as per user preference
                       ($pointsRecord->bursts * 1);

        $pointsRecord->update(['points' => $totalPoints]);
    }
}
