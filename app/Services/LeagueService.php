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
            $matches = LeagueMatch::whereIn('event_id', function($query) use ($season) {
                $query->select('id')->from('league_events')->where('season_id', $season->id);
            })->where('concluded', true)->get();

            foreach ($matches as $match) {
                $this->applyMatchResult($season->id, $match);
            }
        });
    }

    /**
     * Apply a single match result to the standings.
     * GX Rule: Points = the actual score earned in each match.
     * If a player scores 6 in a match, they get 6 points added to standings.
     * Tiebreaker: xtremes count, then wins.
     */
    private function applyMatchResult(int $seasonId, LeagueMatch $match): void
    {
        if (!$match->player_a_id || !$match->player_b_id) return;

        // Player A gets their actual match score as league points
        $this->updatePlayerStats($seasonId, $match->player_a_id, [
            'score' => (int) $match->score_a,
            'win' => $match->winner_id == $match->player_a_id,
            'xtremes' => (int) $match->xtreme_a,
            'spins' => (int) $match->spin_a,
            'overs' => (int) $match->over_a,
            'bursts' => (int) $match->burst_a,
        ]);

        // Player B gets their actual match score as league points
        $this->updatePlayerStats($seasonId, $match->player_b_id, [
            'score' => (int) $match->score_b,
            'win' => $match->winner_id == $match->player_b_id,
            'xtremes' => (int) $match->xtreme_b,
            'spins' => (int) $match->spin_b,
            'overs' => (int) $match->over_b,
            'bursts' => (int) $match->burst_b,
        ]);
    }

    private function updatePlayerStats(int $seasonId, int $playerId, array $stats): void
    {
        $pointsRecord = LeaguePoints::firstOrCreate(
            ['season_id' => $seasonId, 'player_id' => $playerId]
        );

        // Points = direct sum of match scores (Spin=1, Over=2, Burst=2, Xtreme=3)
        $pointsRecord->increment('points', $stats['score']);
        $pointsRecord->increment('wins', $stats['win'] ? 1 : 0);
        $pointsRecord->increment('losses', $stats['win'] ? 0 : 1);
        $pointsRecord->increment('xtremes', $stats['xtremes']);
        $pointsRecord->increment('spins', $stats['spins']);
        $pointsRecord->increment('overs', $stats['overs']);
        $pointsRecord->increment('bursts', $stats['bursts']);
    }
}
