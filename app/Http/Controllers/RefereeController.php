<?php

namespace App\Http\Controllers;

use App\Enums\MatchActionType;
use App\Models\LeagueMatch;
use App\Models\MatchAction;
use App\Services\RefereeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RefereeController extends Controller
{
    public function __construct(
        protected RefereeService $refereeService
    ) {}

    public function dashboard(Request $request)
    {
        $userId = auth()->id();
        $filter = $request->query('filter', 'assigned');

        $legacyTab = $request->query('tab');
        $recoveryOnly = false;

        if ($legacyTab === 'mine') {
            $filter = 'assigned';
        } elseif ($legacyTab === 'available') {
            $filter = 'others';
        } elseif ($legacyTab === 'recovery') {
            $filter = 'assigned';
            $recoveryOnly = true;
        }

        $baseQuery = LeagueMatch::with([
            'event.season',
            'playerA.user',
            'playerB.user',
            'referee',
            'winner',
        ]);

        $assignedPending = (clone $baseQuery)
            ->where('referee_user_id', $userId)
            ->where('concluded', false)
            ->when($recoveryOnly, fn ($query) => $query->where('is_recovery', true))
            ->orderBy('created_at', 'desc')
            ->get();

        $othersPending = (clone $baseQuery)
            ->where('concluded', false)
            ->where(function ($query) use ($userId) {
                $query->whereNull('referee_user_id')
                    ->orWhere('referee_user_id', '!=', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $finalized = (clone $baseQuery)
            ->where('referee_user_id', $userId)
            ->where('concluded', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        $matches = match ($filter) {
            'others' => $othersPending,
            'finalized' => $finalized,
            default => $assignedPending,
        };

        return Inertia::render('Referee/Dashboard', [
            'matches' => $matches,
            'filter' => $filter,
            'stats' => [
                'assigned_pending' => $assignedPending->count(),
                'others_pending' => $othersPending->count(),
                'completed' => $finalized->count(),
            ],
        ]);
    }

    public function takeMatch(LeagueMatch $match)
    {
        $userId = auth()->id();

        $pIdA = $match->playerA->user_id ?? null;
        $pIdB = $match->playerB->user_id ?? null;

        if ($pIdA === $userId || $pIdB === $userId) {
            return back()->with('error', 'No puedes arbitrar tu propia partida.');
        }

        $match->update(['referee_user_id' => $userId]);

        return redirect()->route('referee.match.panel', $match->id)->with('success', '¡Partida tomada exitosamente!');
    }

    public function panel(LeagueMatch $match)
    {
        $match->load(['event.season', 'playerA.user', 'playerB.user', 'winner', 'referee']);

        $actionsData = MatchAction::where('match_id', $match->id)->orderBy('created_at', 'asc')->get();

        return Inertia::render('Referee/Panel', [
            'matchData' => $match,
            'actionsList' => $actionsData,
            'canArbitrate' => (auth()->id() === $match->referee_user_id || auth()->user()->role === 'admin')
        ]);
    }

    public function addAction(Request $request, LeagueMatch $match)
    {
        $validated = $request->validate([
            'side' => 'required|in:a,b',
            'action_type' => 'required|in:spin,over,burst,xtreme,strike'
        ]);

        try {
            $type = MatchActionType::from($validated['action_type']);
            $updatedMatch = $this->refereeService->addAction($match, $validated['side'], $type, auth()->id());

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Acción registrada.',
                    ...$this->buildPanelPayload($updatedMatch),
                ]);
            }

            return back();
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function undoAction(Request $request, LeagueMatch $match)
    {
        try {
            $updatedMatch = $this->refereeService->undoLastAction($match);

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Última acción deshecha.',
                    ...$this->buildPanelPayload($updatedMatch),
                ]);
            }

            return back();
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function resetMatch(Request $request, LeagueMatch $match)
    {
        try {
            $updatedMatch = $this->refereeService->resetMatch($match);

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Match reiniciado.',
                    ...$this->buildPanelPayload($updatedMatch),
                ]);
            }

            return back()->with('success', 'Match reiniciado.');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function finalizeMatch(Request $request, LeagueMatch $match)
    {
        $validated = $request->validate([
            'score_a' => 'required|integer',
            'score_b' => 'required|integer',
            'winner_id' => 'required|integer',
        ]);

        $match->update([
            'score_a' => $validated['score_a'],
            'score_b' => $validated['score_b'],
            'winner_id' => $validated['winner_id'],
            'concluded' => true,
            'concluded_at' => now(),
        ]);

        $winnerName = $validated['winner_id'] == $match->player_a_id
            ? ($match->playerA?->blader_name ?: $match->playerA?->real_name ?: 'Jugador A')
            : ($match->playerB?->blader_name ?: $match->playerB?->real_name ?: 'Jugador B');

        $nextAssignedMatch = LeagueMatch::query()
            ->where('referee_user_id', auth()->id())
            ->where('concluded', false)
            ->where('id', '!=', $match->id)
            ->orderBy('event_id')
            ->orderBy('created_at')
            ->first();

        $payload = [
            'success' => true,
            'winner_name' => $winnerName,
            'score_a' => (int) $validated['score_a'],
            'score_b' => (int) $validated['score_b'],
            'next_match_url' => $nextAssignedMatch ? route('referee.match.panel', $nextAssignedMatch->id) : null,
            'list_url' => route('referee.dashboard', ['filter' => 'assigned']),
            'message' => 'Match finalizado y puntuación guardada.',
        ];

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json($payload);
        }

        return redirect()
            ->route('referee.dashboard', ['filter' => 'assigned'])
            ->with('success', 'Match finalizado. Ganador: ' . $winnerName . '.');
    }

    public function show(LeagueMatch $match)
    {
        $match->load(['event.season', 'playerA.user', 'playerB.user', 'referee', 'winner']);
        $actionsData = MatchAction::where('match_id', $match->id)->orderBy('created_at', 'asc')->get();

        return Inertia::render('Referee/Show', [
            'match' => $match,
            'actions' => $actionsData
        ]);
    }

    private function buildPanelPayload(LeagueMatch $match): array
    {
        $actions = MatchAction::query()
            ->where('match_id', $match->id)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'side', 'action_type', 'created_at']);

        return [
            'match' => [
                'id' => $match->id,
                'score_a' => $match->score_a,
                'score_b' => $match->score_b,
                'spin_a' => $match->spin_a,
                'spin_b' => $match->spin_b,
                'over_a' => $match->over_a,
                'over_b' => $match->over_b,
                'burst_a' => $match->burst_a,
                'burst_b' => $match->burst_b,
                'xtreme_a' => $match->xtreme_a,
                'xtreme_b' => $match->xtreme_b,
                'strikes_a' => $match->strikes_a,
                'strikes_b' => $match->strikes_b,
                'winner_id' => $match->winner_id,
                'concluded' => (bool) $match->concluded,
            ],
            'actions' => $actions,
        ];
    }
}
