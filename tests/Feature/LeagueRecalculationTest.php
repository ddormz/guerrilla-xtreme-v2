<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Models\LeagueEvent;
use App\Models\LeagueMatch;
use App\Models\LeaguePlayer;
use App\Models\LeaguePoints;
use App\Models\LeagueSeason;
use App\Services\LeagueService;
use App\Services\RefereeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueRecalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculate_season_points_only_uses_league_events(): void
    {
        $season = LeagueSeason::create(['name' => 'Temporada Test']);
        [$playerA, $playerB] = $this->createPlayers();

        $leagueEvent = LeagueEvent::create([
            'season_id' => $season->id,
            'name' => 'Fecha de Liga',
            'event_type' => EventType::Liga,
        ]);

        $tournamentEvent = LeagueEvent::create([
            'season_id' => $season->id,
            'name' => 'Torneo GX',
            'event_type' => EventType::Torneo,
        ]);

        LeagueMatch::create([
            'event_id' => $leagueEvent->id,
            'player_a_id' => $playerA->id,
            'player_b_id' => $playerB->id,
            'score_a' => 4,
            'score_b' => 2,
            'winner_id' => $playerA->id,
            'spin_a' => 1,
            'burst_a' => 1,
            'xtreme_b' => 1,
            'concluded' => true,
        ]);

        LeagueMatch::create([
            'event_id' => $tournamentEvent->id,
            'player_a_id' => $playerA->id,
            'player_b_id' => $playerB->id,
            'score_a' => 7,
            'score_b' => 0,
            'winner_id' => $playerA->id,
            'xtreme_a' => 2,
            'concluded' => true,
        ]);

        LeaguePoints::create([
            'season_id' => $season->id,
            'player_id' => $playerA->id,
            'points' => 99,
            'wins' => 99,
            'losses' => 99,
            'xtremes' => 99,
            'spins' => 99,
            'overs' => 99,
            'bursts' => 99,
        ]);

        app(LeagueService::class)->recalculateSeasonPoints($season);

        $this->assertDatabaseHas('league_points', [
            'season_id' => $season->id,
            'player_id' => $playerA->id,
            'points' => 4,
            'wins' => 1,
            'losses' => 0,
            'xtremes' => 0,
            'spins' => 1,
            'overs' => 0,
            'bursts' => 1,
        ]);

        $this->assertDatabaseHas('league_points', [
            'season_id' => $season->id,
            'player_id' => $playerB->id,
            'points' => 2,
            'wins' => 0,
            'losses' => 1,
            'xtremes' => 1,
            'spins' => 0,
            'overs' => 0,
            'bursts' => 0,
        ]);
    }

    public function test_finalizing_a_tournament_match_does_not_modify_league_points(): void
    {
        $season = LeagueSeason::create(['name' => 'Temporada Test']);
        [$playerA, $playerB] = $this->createPlayers();

        $tournamentEvent = LeagueEvent::create([
            'season_id' => $season->id,
            'name' => 'Torneo GX',
            'event_type' => EventType::Torneo,
        ]);

        LeaguePoints::create([
            'season_id' => $season->id,
            'player_id' => $playerA->id,
            'points' => 9,
            'wins' => 2,
            'losses' => 1,
            'xtremes' => 3,
            'spins' => 2,
            'overs' => 1,
            'bursts' => 1,
        ]);

        $match = LeagueMatch::create([
            'event_id' => $tournamentEvent->id,
            'player_a_id' => $playerA->id,
            'player_b_id' => $playerB->id,
            'score_a' => 4,
            'score_b' => 1,
            'xtreme_a' => 1,
        ]);

        app(RefereeService::class)->finalizeMatch($match);

        $this->assertDatabaseHas('league_points', [
            'season_id' => $season->id,
            'player_id' => $playerA->id,
            'points' => 9,
            'wins' => 2,
            'losses' => 1,
            'xtremes' => 3,
            'spins' => 2,
            'overs' => 1,
            'bursts' => 1,
        ]);

        $this->assertTrue($match->fresh()->concluded);
        $this->assertSame($playerA->id, $match->fresh()->winner_id);
    }

    /**
     * @return array{0: LeaguePlayer, 1: LeaguePlayer}
     */
    private function createPlayers(): array
    {
        return [
            LeaguePlayer::create(['real_name' => 'Jugador A']),
            LeaguePlayer::create(['real_name' => 'Jugador B']),
        ];
    }
}
