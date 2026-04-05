<?php

namespace App\Services;

use App\Models\LeagueMatch;
use App\Models\MatchAction;
use App\Enums\MatchActionType;
use App\Events\MatchUpdated;
use Illuminate\Support\Facades\DB;

class RefereeService
{
    public function __construct(
        protected LeagueService $leagueService
    ) {}

    /**
     * Record a new action for a match.
     * SOLID: SRP - Handles action state transitions and scoring.
     */
    public function addAction(LeagueMatch $match, string $side, MatchActionType $type, int $refereeId): LeagueMatch
    {
        return DB::transaction(function () use ($match, $side, $type, $refereeId) {
            if ($match->concluded) {
                throw new \Exception('El match ya ha finalizado.');
            }

            // 1. Create action log
            MatchAction::create([
                'match_id' => $match->id,
                'side' => $side,
                'action_type' => $type,
                'created_by' => $refereeId,
            ]);

            // 2. Update match scores/stats
            $points = $type->points();
            $scoreField = 'score_' . $side;
            $match->$scoreField += $points;

            // GX Rule: If points are scored, reset strikes for BOTH sides
            if ($points > 0) {
                $match->strikes_a = 0;
                $match->strikes_b = 0;
            }

            // Update specific finish type count
            $statField = strtolower($type->value) . '_' . $side;
            if (in_array($type, [MatchActionType::Xtreme, MatchActionType::Spin, MatchActionType::Over, MatchActionType::Burst])) {
                $match->$statField += 1;
            }

            // 3. Handle Special actions
            if ($type === MatchActionType::Strike) {
                $strikeField = 'strikes_' . $side;
                $match->$strikeField += 1;
                
                // GX Rule: Consecutive 2 strikes = 1 point to the rival
                if ($match->$strikeField >= 2) {
                    $otherSide = $side === 'a' ? 'b' : 'a';
                    $otherScoreField = 'score_' . $otherSide;
                    $match->$otherScoreField += 1;
                    $match->$strikeField = 0; // Reset after it becomes a point
                }
            }

            $match->save();

            // 4. Check for automatic conclusion (e.g., reached points cap)
            // Points cap for GX is typically 3 points, but can be configurable.
            if ($match->score_a >= 4 || $match->score_b >= 4) {
                $this->finalizeMatch($match);
            }

            // 5. Broadcast update
            broadcast(new MatchUpdated($match))->toOthers();

            return $match;
        });
    }

    /**
     * Undo the last match action.
     */
    public function undoLastAction(LeagueMatch $match): LeagueMatch
    {
        return DB::transaction(function () use ($match) {
            $lastAction = MatchAction::where('match_id', $match->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lastAction) return $match;

            $side = $lastAction->side;
            $type = $lastAction->action_type;

            // Deduct score
            $scoreField = 'score_' . $side;
            $match->$scoreField -= $type->points();

            // Deduct stat
            $statField = strtolower($type->value) . '_' . $side;
            if (in_array($type, [MatchActionType::Xtreme, MatchActionType::Spin, MatchActionType::Over, MatchActionType::Burst])) {
                $match->$statField = max(0, $match->$statField - 1);
            }

            if ($type === MatchActionType::Strike) {
                $strikeField = 'strikes_' . $side;
                
                // If we are undoing the strike that gave a point to the rival
                if ($match->$strikeField % 2 === 0) {
                    $otherSide = $side === 'a' ? 'b' : 'a';
                    $otherScoreField = 'score_' . $otherSide;
                    $match->$otherScoreField -= 1;
                }

                $match->$strikeField = max(0, $match->$strikeField - 1);
            }

            $lastAction->delete();
            $match->concluded = false; // Re-open if it was closed
            $match->save();

            $match->load('event.season');
            $this->leagueService->recalculateSeasonPoints($match->event->season);

            broadcast(new MatchUpdated($match))->toOthers();

            return $match;
        });
    }

    public function resetMatch(LeagueMatch $match): LeagueMatch
    {
        return DB::transaction(function () use ($match) {
            MatchAction::where('match_id', $match->id)->delete();
            $match->update([
                'score_a' => 0,
                'score_b' => 0,
                'xtreme_a' => 0,
                'xtreme_b' => 0,
                'spin_a' => 0,
                'spin_b' => 0,
                'over_a' => 0,
                'over_b' => 0,
                'burst_a' => 0,
                'burst_b' => 0,
                'strikes_a' => 0,
                'strikes_b' => 0,
                'concluded' => false,
                'winner_id' => null,
            ]);

            $match->load('event.season');
            $this->leagueService->recalculateSeasonPoints($match->event->season);

            broadcast(new MatchUpdated($match))->toOthers();

            return $match;
        });
    }

    public function finalizeMatch(LeagueMatch $match): void
    {
        $match->concluded = true;
        $match->winner_id = $match->score_a > $match->score_b ? $match->player_a_id : $match->player_b_id;
        $match->save();

        $match->load('event.season');
        $this->leagueService->recalculateSeasonPoints($match->event->season);

        broadcast(new MatchUpdated($match))->toOthers();
    }
}
