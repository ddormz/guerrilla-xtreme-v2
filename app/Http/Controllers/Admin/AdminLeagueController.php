<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventType;
use App\Enums\SeasonStatus;
use App\Http\Controllers\Controller;
use App\Models\LeagueAttendance;
use App\Models\LeagueEvent;
use App\Models\LeagueMatch;
use App\Models\LeaguePlayer;
use App\Models\LeagueSeason;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminLeagueController extends Controller
{
    public function index(): Response
    {
        $seasons = LeagueSeason::orderBy('created_at', 'desc')->get();
        $events = LeagueEvent::with('season:id,name')
            ->select([
                'id',
                'season_id',
                'name',
                'event_type',
                'event_date',
                'time',
                'status',
                'description',
                'rules',
                'prizes',
                'registration_cost',
                'bank_name',
                'account_type',
                'account_holder',
                'account_number',
                'account_email',
                'payment_instructions',
                'show_on_index',
                'location',
            ])
            ->orderBy('event_date', 'desc')
            ->limit(20)
            ->get();

        $users = User::whereIn('role', [
            \App\Enums\UserRole::MiembroGx->value,
            \App\Enums\UserRole::Admin->value,
            \App\Enums\UserRole::ArbitroGx->value,
            \App\Enums\UserRole::Miembro->value,
        ])->get(['id', 'name', 'blader_name']);

        $players = LeaguePlayer::with('user')
            ->withCount([
                'points as total_points' => fn ($q) => $q->select(\DB::raw('COALESCE(SUM(points), 0)')),
                'points as total_wins' => fn ($q) => $q->select(\DB::raw('COALESCE(SUM(wins), 0)')),
                'points as total_losses' => fn ($q) => $q->select(\DB::raw('COALESCE(SUM(losses), 0)')),
                'points as total_xtremes' => fn ($q) => $q->select(\DB::raw('COALESCE(SUM(xtremes), 0)')),
            ])
            ->get();

        $pendingLeaguePayments = LeagueAttendance::query()
            ->join('league_events', 'league_events.id', '=', 'league_attendance.event_id')
            ->join('league_seasons', 'league_seasons.id', '=', 'league_events.season_id')
            ->join('league_players', 'league_players.id', '=', 'league_attendance.player_id')
            ->leftJoin('users', 'users.id', '=', 'league_players.user_id')
            ->where('league_events.event_type', EventType::Liga->value)
            ->where('league_attendance.present', true)
            ->where(function ($query) {
                $query->where('league_attendance.paid', false)
                    ->orWhereNull('league_attendance.paid');
            })
            ->orderByDesc('league_events.event_date')
            ->select([
                'league_attendance.event_id',
                'league_attendance.player_id',
                'league_events.name as event_name',
                'league_events.event_date as event_date',
                \DB::raw('COALESCE(NULLIF(league_events.registration_cost, 0), NULLIF(league_seasons.precio_inscripcion, 0), 0) as pending_amount'),
                'league_players.blader_name',
                'users.name as user_name',
            ])
            ->get()
            ->map(fn ($row) => [
                'event_id' => (int) $row->event_id,
                'player_id' => (int) $row->player_id,
                'event_name' => $row->event_name,
                'event_date' => $row->event_date ? Carbon::parse($row->event_date)->format('Y-m-d H:i:s') : null,
                'blader_name' => $row->blader_name ?: $row->user_name ?: 'Blader',
                'pending_amount' => (float) $row->pending_amount,
            ]);

        return Inertia::render('Admin/League/Index', [
            'seasons' => $seasons,
            'events' => $events,
            'users' => $users,
            'players' => $players,
            'pendingLeaguePayments' => $pendingLeaguePayments,
        ]);
    }

    public function storeSeason(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $season = LeagueSeason::create($validated + ['status' => SeasonStatus::InPreparation]);

        AuditLogger::log('create_season', 'LeagueSeason', $season->id, ['name' => $season->name]);

        return back()->with('success', 'Temporada creada correctamente.');
    }

    public function storeEvent(Request $request)
    {
        $validated = $request->validate([
            'season_id' => 'required|exists:league_seasons,id',
            'name' => 'required|string|max:255',
            'event_type' => ['required', 'string', Rule::in([EventType::Liga->value, EventType::Torneo->value])],
            'event_date' => 'required|date',
            'description' => 'nullable|string',
            'rules' => 'nullable|string',
            'location' => 'nullable|string',
            'time' => 'nullable|string',
            'prizes'            => 'nullable|string',
            'registration_cost' => 'nullable|numeric|min:0',
            'bank_name'         => 'nullable|string|max:100',
            'account_type'      => 'nullable|string|max:100',
            'account_holder'    => 'nullable|string|max:100',
            'account_number'    => 'nullable|string|max:50',
            'account_email'     => 'nullable|email|max:100',
            'payment_instructions' => 'nullable|string',
            'show_on_index'     => 'boolean',
        ]);

        $isLeagueEvent = ($validated['event_type'] ?? null) === EventType::Liga->value;

        if ($isLeagueEvent) {
            foreach (['description', 'rules', 'prizes', 'bank_name', 'account_type', 'account_holder', 'account_number', 'account_email', 'payment_instructions'] as $field) {
                $validated[$field] = null;
            }
        }

        $resolvedTime = !empty($validated['time'])
            ? $validated['time']
            : Carbon::parse($validated['event_date'])->format('H:i');

        $event = LeagueEvent::create([
            'season_id' => $validated['season_id'],
            'name' => $validated['name'],
            'event_date' => $validated['event_date'],
            'event_type' => EventType::from($validated['event_type']),
            'status' => 'programado',
            'description' => $validated['description'] ?? null,
            'rules' => $validated['rules'] ?? null,
            'location' => $validated['location'] ?? null,
            'time' => $resolvedTime,
            'prizes'            => $validated['prizes'] ?? null,
            'registration_cost' => $validated['registration_cost'] ?? 0,
            'bank_name'         => $validated['bank_name'] ?? null,
            'account_type'      => $validated['account_type'] ?? null,
            'account_holder'    => $validated['account_holder'] ?? null,
            'account_number'    => $validated['account_number'] ?? null,
            'account_email'     => $validated['account_email'] ?? null,
            'payment_instructions' => $validated['payment_instructions'] ?? null,
            'show_on_index'     => $validated['show_on_index'] ?? false,
        ]);

        AuditLogger::log('create_league_event', 'LeagueEvent', $event->id, ['name' => $event->name]);

        return back()->with('success', 'Evento programado correctamente.');
    }

    public function addPlayer(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'avatar' => 'nullable|image',
        ]);

        $user = User::find($validated['user_id']);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('players/avatars', 'public');
        }

        $player = LeaguePlayer::firstOrCreate(
            ['user_id' => $user->id],
            [
                'real_name' => $user->name,
                'blader_name' => collect([$user->blader_name, $user->name])->filter()->first(),
                'avatar_path' => $avatarPath,
                'wins' => 0,
                'losses' => 0,
                'points' => 0,
                'xtremes' => 0,
                'overs' => 0,
                'bursts' => 0,
                'active' => true,
            ]
        );

        if (!$player->wasRecentlyCreated && $avatarPath) {
            $player->update(['avatar_path' => $avatarPath]);
        }

        AuditLogger::log('add_league_player', 'LeaguePlayer', $player->id, [
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Blader anadido al roster oficial.');
    }

    public function addAllGxMembers()
    {
        $gxMembers = User::where('role', 'miembro_gx')->get();

        foreach ($gxMembers as $user) {
            LeaguePlayer::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'real_name' => $user->name,
                    'blader_name' => collect([$user->blader_name, $user->name])->filter()->first(),
                    'wins' => 0,
                    'losses' => 0,
                    'points' => 0,
                    'xtremes' => 0,
                    'overs' => 0,
                    'bursts' => 0,
                    'active' => true,
                ]
            );
        }

        return back()->with('success', 'Todos los miembros GX fueron anadidos al roster de la liga.');
    }

    public function updateEvent(Request $request, LeagueEvent $event)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'event_type' => ['required', 'string', Rule::in([EventType::Liga->value, EventType::Torneo->value])],
            'event_date' => 'required|date',
            'description' => 'nullable|string',
            'rules' => 'nullable|string',
            'location' => 'nullable|string',
            'time' => 'nullable|string',
            'prizes'            => 'nullable|string',
            'registration_cost' => 'nullable|numeric|min:0',
            'bank_name'         => 'nullable|string|max:100',
            'account_type'      => 'nullable|string|max:100',
            'account_holder'    => 'nullable|string|max:100',
            'account_number'    => 'nullable|string|max:50',
            'account_email'     => 'nullable|email|max:100',
            'payment_instructions' => 'nullable|string',
            'show_on_index'     => 'boolean',
        ]);

        $isLeagueEvent = ($validated['event_type'] ?? null) === EventType::Liga->value;

        if ($isLeagueEvent) {
            foreach (['description', 'rules', 'prizes', 'bank_name', 'account_type', 'account_holder', 'account_number', 'account_email', 'payment_instructions'] as $field) {
                $validated[$field] = null;
            }
        }

        $resolvedTime = !empty($validated['time'])
            ? $validated['time']
            : Carbon::parse($validated['event_date'])->format('H:i');

        $event->update([
            ...$validated,
            'time' => $resolvedTime,
            'event_type' => EventType::from($validated['event_type']),
        ]);

        AuditLogger::log('update_league_event', 'LeagueEvent', $event->id, ['name' => $event->name]);

        return back()->with('success', 'Evento actualizado correctamente.');
    }

    public function destroyEvent(LeagueEvent $event)
    {
        $eventId = $event->id;
        $eventName = $event->name;
        $event->delete();

        AuditLogger::log('delete_league_event', 'LeagueEvent', $eventId, ['name' => $eventName]);

        return redirect()->route('admin.league.index')->with('success', 'Evento eliminado.');
    }

    public function updateSeason(Request $request, LeagueSeason $season)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $season->update($validated);

        AuditLogger::log('update_season', 'LeagueSeason', $season->id, $validated);

        return back()->with('success', 'Temporada actualizada correctamente.');
    }

    public function showEvent(LeagueEvent $event): Response
    {
        $event->load([
            'season:id,name',
            'registrations' => fn ($q) => $q
                ->select([
                    'id',
                    'event_id',
                    'blader_name',
                    'first_name',
                    'last_name',
                    'whatsapp',
                    'email',
                    'is_rex_registered',
                    'generated_user_id',
                    'proof_path',
                    'payment_option',
                    'status',
                    'payment_date',
                    'validation_notes',
                    'created_at',
                ])
                ->orderByDesc('created_at'),
            'registrations.generatedUser:id,name,email',
        ]);

        $players = collect();
        $attendances = collect();
        $referees = collect();

        if ($event->event_type !== EventType::Torneo) {
            $event->load([
                'matches' => fn ($q) => $q->orderBy('id'),
                'matches.player_a',
                'matches.player_b',
                'matches.referee:id,name,blader_name',
                'matches.winner',
            ]);

            $players = LeaguePlayer::with('user:id,avatar_path')
                ->where('active', true)
                ->get(['id', 'user_id', 'blader_name', 'avatar_path', 'active']);

            $attendances = LeagueAttendance::where('event_id', $event->id)
                ->get()
                ->mapWithKeys(fn (LeagueAttendance $attendance) => [
                    $attendance->player_id => [
                        'present' => (bool) $attendance->present,
                        'paid' => (bool) $attendance->paid,
                    ],
                ]);

            $referees = User::whereIn('role', ['admin', 'arbitro_gx'])->get(['id', 'name', 'blader_name']);
        } else {
            $event->setRelation('matches', collect());
        }

        return Inertia::render('Admin/League/Show', [
            'event' => $event,
            'players' => $players,
            'attendances' => $attendances,
            'referees' => $referees,
        ]);
    }

    public function approveTournamentRegistration(TournamentRegistration $registration)
    {
        $registration->update([
            'status' => 'confirmed',
            'validated_at' => now(),
        ]);

        $event = $registration->event;
        $recipient = $registration->email;
        $bladerName = $this->registrationDisplayName($registration);
        $eventName = $event?->name ?? 'Torneo GX';
        $eventRules = $event?->rules;
        $generatedUserId = $registration->generated_user_id;

        if (!empty($recipient)) {
            // Send mail after response to avoid blocking admin UX.
            app()->terminating(function () use ($recipient, $bladerName, $eventName, $eventRules, $generatedUserId) {
                try {
                    $body = '<p>¡Hola <strong>' . htmlspecialchars($bladerName) . '</strong>!</p>'
                        . '<p>Tu pago ha sido validado y tu inscripción al torneo <strong>' . htmlspecialchars($eventName) . '</strong> está <strong style="color:#22c55e;">Confirmada</strong>.</p>'
                        . '<div class="highlight-box">'
                        . '<p style="margin:0 0 8px; font-weight:600;">🚀 Instrucciones para R.E.X:</p>'
                        . '<p style="margin:0 0 12px; font-size:0.9rem;">Debes ingresar a <a href="https://royal-evolution-x.cl" style="color:#E10600; font-weight:700;">royal-evolution-x.cl</a> con tu cuenta para ver el bracket y reportar tus resultados el día del evento.</p>'
                        . ($generatedUserId
                            ? '<p style="margin:0; font-size:0.85rem; color:#94a3b8;">* Hemos creado tu usuario en R.E.X. Acceso por defecto: <strong>usuario ' . htmlspecialchars($recipient) . '</strong> y <strong>contraseña abcd1234</strong>.</p>'
                            : '')
                        . '</div>'
                        . '<div style="margin-top:20px; padding:15px; background:rgba(255,255,255,0.03); border-radius:8px; border-left:4px solid #E10600;">'
                        . '<h4 style="margin:0 0 10px; color:#E10600; text-transform:uppercase; font-size:0.8rem;">Bases y Restricciones:</h4>'
                        . '<div style="font-size:0.85rem; line-height:1.5;">' . ($eventRules ? nl2br(htmlspecialchars($eventRules)) : 'Sin reglas específicas reportadas. Favor seguir el reglamento oficial de Guerrilla Xtrem.') . '</div>'
                        . '</div>'
                        . '<p style="margin-top:20px;">¡Nos vemos en la arena!</p>';

                    Mail::to($recipient)->send(
                        new \App\Mail\GxStyledMail(
                            subject: 'Inscripción Confirmada - ' . $eventName,
                            heading: 'Inscripción Validada Correctamente',
                            body: $body,
                            ctaText: 'Ver R.E.X',
                            ctaUrl: 'https://royal-evolution-x.cl',
                        )
                    );
                } catch (\Throwable $e) {
                    Log::error('Failed to send tournament confirmation email: ' . $e->getMessage());
                }
            });
        }

        AuditLogger::log('approve_tournament_registration', 'TournamentRegistration', $registration->id, [
            'event_id' => $registration->event_id,
            'blader_name' => $registration->blader_name,
        ]);

        return back()->with('success', 'Inscripción confirmada y correo enviado.');
    }

    public function rejectTournamentRegistration(Request $request, TournamentRegistration $registration)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $registration->update([
            'status' => 'rejected',
            'validation_notes' => $validated['reason'],
        ]);

        AuditLogger::log('reject_tournament_registration', 'TournamentRegistration', $registration->id, [
            'event_id' => $registration->event_id,
            'reason' => $validated['reason'],
        ]);

        $event = $registration->event;
        $eventName = $event?->name ?? 'Torneo GX';
        $recipient = $registration->email;
        $bladerName = $this->registrationDisplayName($registration);
        $reason = $validated['reason'];

        if (!empty($recipient)) {
            app()->terminating(function () use ($recipient, $bladerName, $eventName, $reason) {
                try {
                    $body = '<p>Hola <strong>' . htmlspecialchars($bladerName) . '</strong>,</p>'
                        . '<p>Tu preinscripción al torneo <strong>' . htmlspecialchars($eventName) . '</strong> fue <strong style="color:#ef4444;">rechazada</strong>.</p>'
                        . '<div class="highlight-box">'
                        . '<p style="margin:0 0 8px; font-weight:700;">Motivo:</p>'
                        . '<p style="margin:0;">' . nl2br(htmlspecialchars($reason)) . '</p>'
                        . '</div>'
                        . '<p>Si necesitas ayuda, escríbenos por Instagram para revisar tu caso.</p>';

                    Mail::to($recipient)->send(
                        new \App\Mail\GxStyledMail(
                            subject: 'Preinscripción Rechazada - ' . $eventName,
                            heading: 'Estado de tu preinscripción',
                            body: $body,
                            ctaText: 'Contactar por Instagram',
                            ctaUrl: 'https://instagram.com/guerrilla_xtrem',
                        )
                    );
                } catch (\Throwable $e) {
                    Log::error('Failed to send tournament rejection email: ' . $e->getMessage());
                }
            });
        }

        return back()->with('warning', 'Inscripción rechazada.');
    }

    private function registrationDisplayName(TournamentRegistration $registration): string
    {
        if (!empty($registration->blader_name)) {
            return $registration->blader_name;
        }

        $fullName = trim(($registration->first_name ?? '') . ' ' . ($registration->last_name ?? ''));
        return $fullName !== '' ? $fullName : 'Blader';
    }

    public function updateTournamentRegistration(Request $request, TournamentRegistration $registration)
    {
        $validated = $request->validate([
            'blader_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'whatsapp'   => 'nullable|string|max:20',
            'status'     => 'required|string',
        ]);

        $registration->update($validated);

        return back()->with('success', 'Preinscripción actualizada.');
    }

    public function destroyTournamentRegistration(TournamentRegistration $registration)
    {
        $registration->delete();
        return back()->with('success', 'Preinscripción eliminada.');
    }

    public function updateAttendance(Request $request, LeagueEvent $event)
    {
        if ($event->event_type === EventType::Torneo) {
            return back()->withErrors(['error' => 'Los torneos no gestionan asistencia ni pagos en esta plataforma.']);
        }

        $validated = $request->validate([
            'entries' => 'array',
            'entries.*.player_id' => 'required|exists:league_players,id',
            'entries.*.present' => 'required|boolean',
            'entries.*.paid' => 'required|boolean',
        ]);

        LeagueAttendance::where('event_id', $event->id)->delete();

        $entries = collect($validated['entries'] ?? [])->filter(fn ($entry) => $entry['present'] === true);

        if ($entries->isNotEmpty()) {
            LeagueAttendance::insert($entries->map(fn ($entry) => [
                'event_id' => $event->id,
                'player_id' => $entry['player_id'],
                'present' => true,
                'paid' => (bool) $entry['paid'],
            ])->values()->all());
        }

        return back()->with('success', 'Asistencia registrada.');
    }

    public function generateMatches(Request $request, LeagueEvent $event)
    {
        if ($event->event_type === EventType::Torneo) {
            return back()->withErrors(['error' => 'Los torneos no generan cruces en esta plataforma.']);
        }

        $attendees = LeagueAttendance::where('event_id', $event->id)
            ->where('present', true)
            ->pluck('player_id')
            ->toArray();

        if (count($attendees) < 2) {
            return back()->withErrors(['error' => 'Se necesitan al menos 2 jugadores confirmados.']);
        }

        LeagueMatch::where('event_id', $event->id)->where('concluded', false)->delete();

        $matches = [];
        $numPlayers = count($attendees);
        
        for ($i = 0; $i < $numPlayers; $i++) {
            for ($j = $i + 1; $j < $numPlayers; $j++) {
                $matches[] = [
                    'event_id' => $event->id,
                    'player_a_id' => $attendees[$i],
                    'player_b_id' => $attendees[$j],
                    'round_no' => 1,
                    'best_of' => 1,
                    'concluded' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        if (!empty($matches)) {
            LeagueMatch::insert($matches);
        }

        return back()->with('success', count($matches) . ' partidas generadas exitosamente (Todos contra Todos).');
    }

    public function assignReferee(Request $request, LeagueMatch $match)
    {
        $validated = $request->validate([
            'referee_id' => 'nullable|exists:users,id',
        ]);

        $match->update(['referee_user_id' => $validated['referee_id']]);

        return back()->with('success', 'Arbitro asignado a la partida.');
    }

    public function destroyMatch(LeagueMatch $match)
    {
        $match->delete();
        return back()->with('success', 'Partida eliminada.');
    }

    public function autoAssignReferees(Request $request, LeagueEvent $event)
    {
        if ($event->event_type === EventType::Torneo) {
            return back()->withErrors(['error' => 'Los torneos no requieren asignación de árbitros en esta plataforma.']);
        }

        $matches = LeagueMatch::with(['playerA', 'playerB'])->where('event_id', $event->id)->where('concluded', false)->whereNull('referee_user_id')->get();
        if ($matches->isEmpty()) {
            return back()->with('success', 'No hay partidas pendientes sin arbitro asignado.');
        }

        $selectedReferees = $request->input('selected_referees', []);
        
        if (empty($selectedReferees)) {
            $referees = User::whereIn('role', ['admin', 'arbitro_gx', 'miembro'])->pluck('id')->toArray();
        } else {
            $referees = collect($selectedReferees)->map(fn($v) => (int)$v)->toArray();
        }

        if (empty($referees)) {
            return back()->withErrors(['error' => 'No hay arbitros disponibles para asignar.']);
        }

        shuffle($referees);
        $refCount = count($referees);
        $refIndex = 0;
        $assigned = 0;
        $skipped = 0;

        foreach ($matches as $match) {
            $aUserId = $match->playerA?->user_id;
            $bUserId = $match->playerB?->user_id;

            $found = false;
            for ($try = 0; $try < $refCount; $try++) {
                $refId = $referees[($refIndex + $try) % $refCount];
                if ($refId !== $aUserId && $refId !== $bUserId) {
                    $match->update(['referee_user_id' => $refId]);
                    $refIndex = ($refIndex + $try + 1);
                    $assigned++;
                    $found = true;
                    break;
                }
            }
            if (!$found) $skipped++;
        }

        $msg = "Árbitros asignados a $assigned partidas.";
        if ($skipped > 0) $msg .= " ($skipped partidas omitidas por conflicto de auto-arbitraje)";

        return back()->with('success', $msg);
    }

    public function updatePlayer(Request $request, LeaguePlayer $player)
    {
        $validated = $request->validate([
            'blader_name' => 'nullable|string|max:255',
            'real_name' => 'nullable|string|max:255',
            'avatar' => 'nullable|image',
            'active' => 'boolean',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar_path'] = $request->file('avatar')->store('players/avatars', 'public');
        }

        $player->update($validated);

        return back()->with('success', 'Jugador actualizado.');
    }

    public function togglePlayerActive(LeaguePlayer $player)
    {
        $player->update([
            'active' => !$player->active,
        ]);

        AuditLogger::log('toggle_league_player_active', 'LeaguePlayer', $player->id, [
            'active' => $player->active,
        ]);

        $message = $player->active ? 'Jugador activado en el roster oficial.' : 'Jugador desactivado en el roster oficial.';

        return back()->with('success', $message);
    }

    public function destroyPlayer(LeaguePlayer $player)
    {
        $playerId = $player->id;
        $player->delete();

        AuditLogger::log('delete_league_player', 'LeaguePlayer', $playerId);

        return back()->with('success', 'Jugador eliminado del roster oficial.');
    }

    public function destroySeason(LeagueSeason $season)
    {
        $seasonId = $season->id;
        $season->delete();

        AuditLogger::log('delete_season', 'LeagueSeason', $seasonId);

        return back()->with('success', 'Temporada eliminada.');
    }

    /**
     * Toggle the matches_locked flag on an event.
     */
    public function toggleMatchLock(LeagueEvent $event)
    {
        $event->update(['matches_locked' => !$event->matches_locked]);

        $status = $event->matches_locked ? 'bloqueadas' : 'desbloqueadas';
        AuditLogger::log('toggle_match_lock', 'LeagueEvent', $event->id, ['locked' => $event->matches_locked]);

        return back()->with('success', "Partidas $status.");
    }

    /**
     * Add a late player — generates matches ONLY between this player and existing attendees.
     */
    public function addLatePlayer(Request $request, LeagueEvent $event)
    {
        $request->validate([
            'player_id' => 'required|exists:league_players,id',
        ]);

        $newPlayerId = $request->player_id;

        // Mark as present if not already
        LeagueAttendance::updateOrCreate(
            ['event_id' => $event->id, 'player_id' => $newPlayerId],
            ['present' => true]
        );

        // Get all OTHER attendees
        $existingAttendees = LeagueAttendance::where('event_id', $event->id)
            ->where('present', true)
            ->where('player_id', '!=', $newPlayerId)
            ->pluck('player_id')
            ->toArray();

        if (empty($existingAttendees)) {
            return back()->with('error', 'No hay otros jugadores presentes para generar partidas.');
        }

        // Check which matches already exist for this player
        $existingMatches = LeagueMatch::where('event_id', $event->id)
            ->where(function ($q) use ($newPlayerId) {
                $q->where('player_a_id', $newPlayerId)
                  ->orWhere('player_b_id', $newPlayerId);
            })
            ->get();

        $existingOpponents = $existingMatches->map(function ($m) use ($newPlayerId) {
            return $m->player_a_id == $newPlayerId ? $m->player_b_id : $m->player_a_id;
        })->toArray();

        $newMatches = [];
        foreach ($existingAttendees as $opponentId) {
            if (in_array($opponentId, $existingOpponents)) continue;

            $newMatches[] = [
                'event_id' => $event->id,
                'player_a_id' => $newPlayerId,
                'player_b_id' => $opponentId,
                'round_no' => 1,
                'best_of' => 1,
                'concluded' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($newMatches)) {
            LeagueMatch::insert($newMatches);
        }

        $player = LeaguePlayer::find($newPlayerId);
        $name = $player->blader_name ?? "Jugador #{$newPlayerId}";

        AuditLogger::log('add_late_player', 'LeagueEvent', $event->id, [
            'player_id' => $newPlayerId,
            'matches_created' => count($newMatches),
        ]);

        return back()->with('success', "{$name} añadido. " . count($newMatches) . " partidas nuevas generadas.");
    }
}
