<?php

namespace App\Services;

use App\Enums\EventType;
use App\Models\LeagueEvent;
use App\Models\LeagueMatch;
use App\Models\RankingPoints;
use Illuminate\Support\Facades\DB;

class RankingService
{
    /**
     * Recalculate the global ranking from all Torneo+Ranking events.
     * Only counts events from June 2026 onwards.
     */
    public function recalculateRanking(): void
    {
        DB::transaction(function () {
            // Reset all ranking points
            RankingPoints::query()->update([
                'points_for' => 0,
                'points_against' => 0,
                'differential' => 0,
                'wins' => 0,
                'losses' => 0,
                'xtremes' => 0,
                'matches_played' => 0,
            ]);

            // Get all concluded matches from Torneo+Ranking events
            $matches = LeagueMatch::whereHas('event', function ($query) {
                $query->where('event_type', EventType::TorneoRanking->value);
            })->where('concluded', true)->get();

            foreach ($matches as $match) {
                $this->applyMatchToRanking($match);
            }
        });
    }

    /**
     * Apply a single match result to the global ranking.
     * Points = Your score - Opponent's score (differential).
     * Tiebreaker: Xtremes count.
     */
    public function applyMatchToRanking(LeagueMatch $match): void
    {
        if (!$match->player_a_id || !$match->player_b_id) return;

        $scoreA = (int) $match->score_a;
        $scoreB = (int) $match->score_b;
        $winnerIsA = $match->winner_id == $match->player_a_id;

        // Player A
        $this->updateRankingStats($match->player_a_id, [
            'points_for' => $scoreA,
            'points_against' => $scoreB,
            'win' => $winnerIsA,
            'xtremes' => (int) $match->xtreme_a,
        ]);

        // Player B
        $this->updateRankingStats($match->player_b_id, [
            'points_for' => $scoreB,
            'points_against' => $scoreA,
            'win' => !$winnerIsA,
            'xtremes' => (int) $match->xtreme_b,
        ]);
    }

    private function updateRankingStats(int $playerId, array $stats): void
    {
        $record = RankingPoints::firstOrCreate(
            ['player_id' => $playerId],
            ['points_for' => 0, 'points_against' => 0, 'differential' => 0, 'wins' => 0, 'losses' => 0, 'xtremes' => 0, 'matches_played' => 0]
        );

        $record->increment('points_for', $stats['points_for']);
        $record->increment('points_against', $stats['points_against']);
        $record->increment('wins', $stats['win'] ? 1 : 0);
        $record->increment('losses', $stats['win'] ? 0 : 1);
        $record->increment('xtremes', $stats['xtremes']);
        $record->increment('matches_played', 1);

        // Recalculate differential
        $record->update([
            'differential' => $record->points_for - $record->points_against,
        ]);
    }

    public function getStandings()
    {
        $members = \App\Models\User::where('role', \App\Enums\UserRole::MiembroGx)->get()->keyBy('id');
        $points = RankingPoints::with('player')->get()->keyBy('player_id');

        // Merge users who have points but are not MiembroGx (e.g. admins who played)
        foreach ($points as $playerId => $point) {
            if (!$members->has($playerId) && $point->player) {
                $members->put($playerId, $point->player);
            }
        }

        $standings = $members->map(function ($user) use ($points) {
            if ($points->has($user->id)) {
                return $points->get($user->id);
            }

            $p = new RankingPoints([
                'points_for' => 0,
                'points_against' => 0,
                'differential' => 0,
                'wins' => 0,
                'losses' => 0,
                'xtremes' => 0,
                'matches_played' => 0,
            ]);
            $p->player_id = $user->id;
            $p->setRelation('player', $user);
            return $p;
        });

        return $standings->sort(function ($a, $b) {
            if ($a->differential !== $b->differential) {
                return $b->differential <=> $a->differential;
            }
            if ($a->xtremes !== $b->xtremes) {
                return $b->xtremes <=> $a->xtremes;
            }
            return $b->wins <=> $a->wins;
        })->values();
    }
}
