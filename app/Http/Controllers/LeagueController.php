<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Enums\UserRole;
use App\Models\LeagueEvent;
use App\Models\LeagueMatch;
use App\Models\LeaguePlayer;
use App\Models\LeaguePoints;
use App\Models\LeagueSeason;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LeagueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class LeagueController extends Controller
{
    public function __construct(
        protected LeagueService $leagueService
    ) {}

    public function standings(?LeagueSeason $season = null): Response
    {
        if (!auth()->check() || !auth()->user()->role === UserRole::MiembroGx && !auth()->user()->role === UserRole::Admin) {
            return Inertia::render('Home', [
                'flash' => ['error' => 'Solo los Miembros GX pueden ver la tabla de posiciones de la liga.']
            ]);
        }
        $activeSeason = $season?->id ? $season : LeagueSeason::where('status', 'en_curso')->first();

        if (!$activeSeason) {
            $activeSeason = LeagueSeason::orderBy('created_at', 'desc')->first();
        }

        $standings = $activeSeason
            ? $this->leagueService->getStandings($activeSeason)->map(fn ($p) => [
                'id' => $p->id,
                'player_id' => $p->player_id,
                'player_name' => $p->player->display_name,
                'avatar_url' => $p->player->avatar_url,
                'points' => $p->points,
                'wins' => $p->wins,
                'losses' => $p->losses,
                'xtremes' => $p->xtremes,
                'overs' => $p->overs,
                'bursts' => $p->bursts,
            ])
            : [];

        $seasons = LeagueSeason::orderBy('created_at', 'desc')->get(['id', 'name']);

        $totalCollected = $activeSeason
            ? $activeSeason->players()->count() * $activeSeason->precio_inscripcion
            : 0;

        $upcomingEvents = $activeSeason
            ? LeagueEvent::where('season_id', $activeSeason->id)
                ->where('event_date', '>=', now()->startOfDay())
                ->orderBy('event_date', 'asc')
                ->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'date' => $e->event_date->format('d/m/Y H:i'),
                    'is_live' => $e->is_live,
                ])
            : [];

        $pastEvents = $activeSeason
            ? LeagueEvent::where('season_id', $activeSeason->id)
                ->where('event_date', '<', now()->startOfDay())
                ->orderBy('event_date', 'desc')
                ->take(3)
                ->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'date' => $e->event_date->format('d/m/Y H:i'),
                    'is_live' => $e->is_live,
                    'total_matches' => LeagueMatch::where('event_id', $e->id)->count(),
                ])
            : [];

        return Inertia::render('League/Standings', [
            'season' => $activeSeason,
            'standings' => $standings,
            'seasons' => $seasons,
            'totalCollected' => $totalCollected,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
        ]);
    }

    public function events(): Response
    {
        $events = LeagueEvent::with(['season'])
            ->where('event_type', EventType::Torneo)
            ->orderBy('event_date', 'desc')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'date' => $e->event_date?->format('d/m/Y H:i'),
                'event_date' => $e->event_date,
                'type' => $e->event_type->value,
                'status' => $e->status,
                'is_live' => $e->is_live,
                'season_name' => $e->season?->name,
                'description' => $e->description,
                'location' => $e->location,
                'time' => $e->time,
                'prizes' => $e->prizes,
                'rules' => $e->rules,
                'registration_cost' => $e->registration_cost,
            ]);

        return Inertia::render('League/Events', [
            'events' => $events,
        ]);
    }

    public function registrationForm(LeagueEvent $event): Response
    {
        $event->load('season');
        $isRegistered = false;

        if (auth()->check()) {
            $isRegistered = TournamentRegistration::where('event_id', $event->id)
                ->where(function($q) {
                    $q->where('generated_user_id', auth()->id())
                      ->orWhere('email', auth()->user()->email)
                      ->orWhere('blader_name', auth()->user()->blader_name);
                })->exists();
        }

        return Inertia::render('Tournaments/Register', [
            'event' => $event,
            'isRegistered' => $isRegistered,
        ]);
    }

    public function storeRegistration(Request $request, LeagueEvent $event)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'blader_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'whatsapp' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'is_rex_registered' => 'required|boolean',
            'payment_option' => 'required|string|in:now,later',
            'proof' => 'required_if:payment_option,now|image|max:5120', // Max 5MB
        ]);

        // Duplicate prevention
        $query = TournamentRegistration::where('event_id', $event->id);
        if (auth()->check()) {
            $query->where(function($q) use ($validated) {
                $q->where('email', $validated['email'])
                  ->orWhere('blader_name', $validated['blader_name'])
                  ->orWhere('generated_user_id', auth()->id());
            });
        } else {
            $query->where(function($q) use ($validated) {
                $q->where('email', $validated['email'])
                  ->orWhere('blader_name', $validated['blader_name']);
            });
        }

        if ($query->exists()) {
            return back()->with('error', 'Ya te encuentras pre-registrado en este evento.');
        }

        $generatedUserId = null;

        if (!$validated['is_rex_registered']) {
            $existingUser = User::where('email', $validated['email'])->first();
            if (!$existingUser) {
                $generated = User::create([
                    'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                    'blader_name' => $validated['blader_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['whatsapp'],
                    'password' => Hash::make('abcd1234'),
                    'password_temporary' => true,
                    'role' => UserRole::Miembro,
                ]);
                $generatedUserId = $generated->id;
            } else {
                $generatedUserId = $existingUser->id;
            }
        }

        // Handle proof upload
        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('proofs/tournaments', 'public');
        }

        $registration = TournamentRegistration::create([
            'event_id' => $event->id,
            'player_id' => auth()->check() ? auth()->user()->player?->id : null,
            'is_internal' => false,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'blader_name' => $validated['blader_name'],
            'birth_date' => $validated['birth_date'],
            'whatsapp' => $validated['whatsapp'],
            'email' => $validated['email'],
            'is_rex_registered' => $validated['is_rex_registered'],
            'generated_user_id' => $generatedUserId,
            'proof_path' => $proofPath,
            'payment_option' => $validated['payment_option'],
            'status' => 'pending',
            'payment_date' => $validated['payment_option'] === 'now' ? now() : null,
        ]);

        AuditLogger::log('register_tournament', 'TournamentRegistration', $registration->id, [
            'event_id' => $event->id,
            'is_rex_registered' => $validated['is_rex_registered'],
            'generated_user_id' => $generatedUserId,
            'status' => 'pending',
        ]);

        // Send confirmation receipt email
        try {
            $statusText = $validated['payment_option'] === 'now' 
                ? 'Hemos recibido tu pre-inscripción y comprobante de pago. Te notificaremos una vez que lo validemos.'
                : 'Tu pre-inscripción ha sido recibida. Recuerda que debes realizar el pago antes del evento.';

            $body = '<p>Hola <strong>' . htmlspecialchars($validated['blader_name']) . '</strong>,</p>'
                . '<p>' . $statusText . '</p>'
                . '<div class="highlight-box">'
                . '<p style="margin:0 0 4px; font-weight:600; color:#F59E0B;">Estado: En revisión</p>'
                . ($validated['payment_option'] === 'later' 
                    ? '<p style="margin:0; font-weight:bold; color:#E10600;">⚠️ Si 24 horas antes del evento no se ha recibido el pago, tu inscripción será cancelada.</p>'
                    : '<p style="margin:0;">Validaremos tu pago y tu inscripción estará asegurada.</p>')
                . '</div>'
                . '<p>Detalles del evento:</p>'
                . '<ul>'
                . '<li>Fecha: ' . ($event->event_date ? $event->event_date->format('d/m/Y') : 'Por confirmar') . '</li>'
                . ($event->location ? '<li>Ubicación: ' . htmlspecialchars($event->location) . '</li>' : '')
                . '</ul>';

            \Illuminate\Support\Facades\Mail::to($validated['email'])->send(
                new \App\Mail\GxStyledMail(
                    subject: 'Registro Recibido - ' . $event->name,
                    heading: 'Hemos recibido tu registro',
                    body: $body,
                )
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send tournament registration receipt email: " . $e->getMessage());
        }

        $msg = $validated['payment_option'] === 'now' 
            ? 'Registro y comprobante recibidos. Tu inscripción está en revisión.'
            : 'Pre-registro completado. Recuerda pagar antes de las 24hrs previas al evento.';

        return back()->with('success', $msg);
    }

    public function playerProfile(LeaguePlayer $player): Response
    {
        $player->load(['user', 'parent', 'children']);

        $totalMatches = LeagueMatch::where(function ($q) use ($player) {
            $q->where('player_a_id', $player->id)
                ->orWhere('player_b_id', $player->id);
        })->where('concluded', true)->count();

        $totalWins = LeagueMatch::where('winner_id', $player->id)->where('concluded', true)->count();
        $winRate = $totalMatches > 0 ? (float)number_format(($totalWins / $totalMatches) * 100, 1) : 0;

        $pointsBySeason = LeaguePoints::with('season')
            ->where('player_id', $player->id)
            ->orderByDesc('season_id')
            ->get();

        $rankingHistory = $pointsBySeason->map(function ($row) {
            $ordered = LeaguePoints::where('season_id', $row->season_id)
                ->orderByDesc('points')
                ->orderByDesc('wins')
                ->orderByDesc('xtremes')
                ->pluck('player_id')
                ->values();

            $rank = $ordered->search($row->player_id);

            return [
                'season' => $row->season?->name ?? 'Temporada',
                'rank' => $rank !== false ? $rank + 1 : null,
                'points' => $row->points,
                'wins' => $row->wins,
                'losses' => $row->losses,
                'xtremes' => $row->xtremes,
                'overs' => $row->overs,
                'bursts' => $row->bursts,
            ];
        });

        $latest = $pointsBySeason->first();

        $recentMatches = LeagueMatch::with(['event', 'playerA', 'playerB'])
            ->where('concluded', true)
            ->where(function ($q) use ($player) {
                $q->where('player_a_id', $player->id)
                    ->orWhere('player_b_id', $player->id);
            })
            ->orderByDesc('updated_at')
            ->take(10)
            ->get()
            ->map(function ($match) use ($player) {
                $isA = $match->player_a_id === $player->id;
                $opponent = $isA ? $match->playerB : $match->playerA;
                $score = $isA
                    ? ($match->score_a . ' - ' . $match->score_b)
                    : ($match->score_b . ' - ' . $match->score_a);

                return [
                    'id' => $match->id,
                    'event_name' => $match->event?->name ?? 'Evento',
                    'event_date' => $match->event?->event_date?->format('d/m/Y'),
                    'opponent' => $opponent?->display_name ?? 'BYE',
                    'result' => $match->winner_id === $player->id ? 'W' : 'L',
                    'score' => $score,
                ];
            });

        // GX Kinship Logic:
        // HIJO DE: Player who has won against you the most times.
        $nemesisId = LeagueMatch::where(function ($q) use ($player) {
                $q->where('player_a_id', $player->id)->orWhere('player_b_id', $player->id);
            })
            ->where('concluded', true)
            ->get()
            ->map(fn($m) => $m->winner_id !== $player->id ? $m->winner_id : null)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        // PAPÁ DE: Player whom you have won against the most times.
        $victimId = LeagueMatch::where('winner_id', $player->id)
            ->where('concluded', true)
            ->get()
            ->map(fn($m) => $m->player_a_id === $player->id ? $m->player_b_id : $m->player_a_id)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        $nemesis = $nemesisId ? LeaguePlayer::find($nemesisId) : null;
        $victim = $victimId ? LeaguePlayer::find($victimId) : null;

        return Inertia::render('League/PlayerProfile', [
            'player' => [
                'id' => $player->id,
                'name' => $player->real_name,
                'blader_name' => $player->blader_name,
                'display_name' => $player->display_name,
                'avatar_url' => $player->avatar_url,
                'win_rate' => $winRate,
                'nemesis' => $nemesis ? [
                    'id' => $nemesis->id,
                    'name' => $nemesis->display_name,
                    'avatar_url' => $nemesis->avatar_url,
                ] : null,
                'victim' => $victim ? [
                    'id' => $victim->id,
                    'name' => $victim->display_name,
                    'avatar_url' => $victim->avatar_url,
                ] : null,
            ],
            'stats' => [
                'points' => $latest?->points ?? 0,
                'wins' => $latest?->wins ?? 0,
                'losses' => $latest?->losses ?? 0,
                'xtremes' => $latest?->xtremes ?? 0,
                'overs' => $latest?->overs ?? 0,
                'bursts' => $latest?->bursts ?? 0,
            ],
            'rankingHistory' => $rankingHistory,
            'recentMatches' => $recentMatches,
            'globalLeaders' => [
                'xtremes' => LeaguePoints::orderByDesc('xtremes')->first()?->player_id === $player->id,
                'overs' => LeaguePoints::orderByDesc('overs')->first()?->player_id === $player->id,
                'bursts' => LeaguePoints::orderByDesc('bursts')->first()?->player_id === $player->id,
                'wins' => LeaguePoints::orderByDesc('wins')->first()?->player_id === $player->id,
            ],
        ]);
    }
}
