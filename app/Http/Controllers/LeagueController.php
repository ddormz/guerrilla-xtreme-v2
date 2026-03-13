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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
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
            ->select([
                'id',
                'season_id',
                'name',
                'event_date',
                'event_type',
                'status',
                'is_live',
                'description',
                'location',
                'time',
                'prizes',
                'rules',
                'registration_cost',
            ])
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

    public function checkRegistration(Request $request, LeagueEvent $event): JsonResponse
    {
        $validated = $request->validate([
            'blader_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $bladerName = !empty($validated['blader_name']) ? $this->normalizeBladerName($validated['blader_name']) : null;
        $email = !empty($validated['email']) ? $this->normalizeEmail($validated['email']) : null;

        if (empty($bladerName) && empty($email) && !auth()->check()) {
            return response()->json([
                'exists' => false,
                'message' => null,
            ]);
        }

        $query = TournamentRegistration::where('event_id', $event->id);
        $query->where(function ($q) use ($bladerName, $email) {
            if (!empty($bladerName)) {
                $q->whereRaw('LOWER(TRIM(blader_name)) = ?', [mb_strtolower($bladerName)]);
            }

            if (!empty($email)) {
                $method = !empty($bladerName) ? 'orWhereRaw' : 'whereRaw';
                $q->{$method}('LOWER(TRIM(email)) = ?', [$email]);
            }

            if (auth()->check()) {
                $method = !empty($bladerName) || !empty($email) ? 'orWhere' : 'where';
                $q->{$method}('generated_user_id', auth()->id());
            }
        });

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Ya te encuentras pre-registrado en este evento.' : null,
        ]);
    }

    public function storeRegistration(Request $request, LeagueEvent $event)
    {
        $isRexRegistered = $request->boolean('is_rex_registered');
        $requiresContactData = !$isRexRegistered;

        $validated = $request->validate([
            'first_name' => [Rule::requiredIf($requiresContactData), 'nullable', 'string', 'max:255'],
            'last_name' => [Rule::requiredIf($requiresContactData), 'nullable', 'string', 'max:255'],
            'blader_name' => ['required', 'string', 'max:255'],
            'birth_date' => [Rule::requiredIf($requiresContactData), 'nullable', 'date'],
            'whatsapp' => [Rule::requiredIf($requiresContactData), 'nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'is_rex_registered' => ['required', 'boolean'],
            'payment_option' => ['required', Rule::in(['now', 'later'])],
            'proof' => [
                Rule::requiredIf($request->input('payment_option') === 'now'),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ], [
            'first_name.required' => 'Debes ingresar tu nombre.',
            'last_name.required' => 'Debes ingresar tu apellido.',
            'birth_date.required' => 'Debes ingresar tu fecha de nacimiento.',
            'whatsapp.required' => 'Debes ingresar tu WhatsApp.',
            'email.required' => 'Debes ingresar un correo electrónico.',
            'blader_name.required' => 'Debes ingresar tu nombre de Blader.',
            'proof.required' => 'Debes subir el comprobante para confirmar el pago.',
            'proof.mimes' => 'El comprobante debe ser JPG, JPEG, PNG o WEBP.',
            'proof.max' => 'El comprobante no puede superar los 5MB.',
        ]);

        if ($isRexRegistered && auth()->check()) {
            $user = auth()->user();
            $nameParts = collect(explode(' ', trim($user->name ?? '')))->filter()->values();

            $validated['first_name'] = $validated['first_name'] ?: ($nameParts->first() ?? null);
            $validated['last_name'] = $validated['last_name'] ?: ($nameParts->slice(1)->implode(' ') ?: null);
            $validated['email'] = $validated['email'] ?: ($user->email ?? null);
            $validated['whatsapp'] = $validated['whatsapp'] ?: ($user->phone ?? null);
        }

        $validated['first_name'] = $validated['first_name'] ?: null;
        $validated['last_name'] = $validated['last_name'] ?: null;
        $validated['birth_date'] = $validated['birth_date'] ?: null;
        $validated['whatsapp'] = $validated['whatsapp'] ?: null;
        $validated['email'] = $validated['email'] ?: null;
        $validated['blader_name'] = $this->normalizeBladerName($validated['blader_name']);
        $validated['first_name'] = $validated['first_name'] ? trim($validated['first_name']) : null;
        $validated['last_name'] = $validated['last_name'] ? trim($validated['last_name']) : null;
        $validated['email'] = $validated['email'] ? $this->normalizeEmail($validated['email']) : null;

        // Duplicate prevention
        $query = TournamentRegistration::where('event_id', $event->id);
        $query->where(function ($q) use ($validated) {
            $q->whereRaw('LOWER(TRIM(blader_name)) = ?', [mb_strtolower($validated['blader_name'])]);

            if (!empty($validated['email'])) {
                $q->orWhereRaw('LOWER(TRIM(email)) = ?', [$validated['email']]);
            }

            if (auth()->check()) {
                $q->orWhere('generated_user_id', auth()->id());
            }
        });

        if ($query->exists()) {
            return back()->with('error', 'Ya te encuentras pre-registrado en este evento.');
        }

        $generatedUserId = null;

        if (!$validated['is_rex_registered']) {
            $existingUser = User::whereRaw('LOWER(TRIM(email)) = ?', [$validated['email']])->first();
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

        try {
            $registration = DB::transaction(function () use ($event, $validated, $generatedUserId, $proofPath) {
                $duplicateQuery = TournamentRegistration::where('event_id', $event->id)->lockForUpdate();
                $duplicateQuery->where(function ($q) use ($validated) {
                    $q->whereRaw('LOWER(TRIM(blader_name)) = ?', [mb_strtolower($validated['blader_name'])]);

                    if (!empty($validated['email'])) {
                        $q->orWhereRaw('LOWER(TRIM(email)) = ?', [$validated['email']]);
                    }

                    if (auth()->check()) {
                        $q->orWhere('generated_user_id', auth()->id());
                    }
                });

                if ($duplicateQuery->exists()) {
                    throw new \RuntimeException('duplicate_registration');
                }

                return TournamentRegistration::create([
                    'event_id' => $event->id,
                    'player_id' => auth()->check() ? auth()->user()->player?->id : null,
                    'is_internal' => false,
                    'first_name' => $validated['first_name'] ?? null,
                    'last_name' => $validated['last_name'] ?? null,
                    'blader_name' => $validated['blader_name'],
                    'birth_date' => $validated['birth_date'] ?? null,
                    'whatsapp' => $validated['whatsapp'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'is_rex_registered' => $validated['is_rex_registered'],
                    'generated_user_id' => $generatedUserId,
                    'proof_path' => $proofPath,
                    'payment_option' => $validated['payment_option'],
                    'status' => 'pending',
                    'payment_date' => $validated['payment_option'] === 'now' ? now() : null,
                ]);
            }, 3);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'duplicate_registration') {
                return back()->with('error', 'Ya te encuentras pre-registrado en este evento.');
            }

            throw $e;
        }

        AuditLogger::log('register_tournament', 'TournamentRegistration', $registration->id, [
            'event_id' => $event->id,
            'is_rex_registered' => $validated['is_rex_registered'],
            'generated_user_id' => $generatedUserId,
            'status' => 'pending',
        ]);

        $registrationData = [
            'id' => $registration->id,
            'blader_name' => $this->displayBladerName($registration->blader_name, $registration->first_name, $registration->last_name),
            'first_name' => $registration->first_name,
            'last_name' => $registration->last_name,
            'email' => $registration->email,
            'whatsapp' => $registration->whatsapp,
            'is_rex_registered' => (bool) $registration->is_rex_registered,
            'payment_option' => $registration->payment_option,
            'proof_path' => $registration->proof_path,
        ];

        $eventData = [
            'id' => $event->id,
            'name' => $event->name,
            'date' => $event->event_date?->format('d/m/Y'),
            'location' => $event->location,
        ];

        // Send mail after response to keep registration flow fast.
        app()->terminating(function () use ($registrationData, $eventData) {
            $this->sendRegistrantReceiptMail($registrationData, $eventData);
            $this->sendAdminRegistrationAlertMail($registrationData, $eventData);
        });

        $msg = $validated['payment_option'] === 'now' 
            ? 'Registro y comprobante recibidos. Tu inscripción está en revisión.'
            : 'Pre-registro completado. Recuerda pagar antes de las 24hrs previas al evento.';

        return back()->with('success', $msg);
    }

    private function sendRegistrantReceiptMail(array $registrationData, array $eventData): void
    {
        $recipient = $registrationData['email'] ?? null;
        if (empty($recipient)) {
            return;
        }

        try {
            $statusText = ($registrationData['payment_option'] ?? 'now') === 'now'
                ? 'Hemos recibido tu pre-inscripción y comprobante de pago. Te notificaremos una vez que lo validemos.'
                : 'Tu pre-inscripción ha sido recibida. Recuerda que debes realizar el pago antes del evento.';

            $body = '<p>Hola <strong>' . htmlspecialchars($registrationData['blader_name']) . '</strong>,</p>'
                . '<p>' . $statusText . '</p>'
                . '<div class="highlight-box">'
                . '<p style="margin:0 0 4px; font-weight:600; color:#F59E0B;">Estado: En revisión</p>'
                . (($registrationData['payment_option'] ?? 'now') === 'later'
                    ? '<p style="margin:0; font-weight:bold; color:#E10600;">⚠️ Si 24 horas antes del evento no se ha recibido el pago, tu inscripción será cancelada.</p>'
                    : '<p style="margin:0;">Validaremos tu pago y tu inscripción estará asegurada.</p>')
                . '</div>';

            if (($registrationData['is_rex_registered'] ?? true) === false) {
                $body .= '<div class="highlight-box">'
                    . '<p style="margin:0 0 8px; font-weight:700;">Acceso por defecto a R.E.X (usuario nuevo)</p>'
                    . '<ul style="margin:0; padding-left:18px;">'
                    . '<li><strong>Usuario:</strong> ' . htmlspecialchars($registrationData['email'] ?? 'correo no disponible') . '</li>'
                    . '<li><strong>Contraseña temporal:</strong> abcd1234</li>'
                    . '</ul>'
                    . '<p style="margin:8px 0 0;">Te recomendamos cambiar tu contraseña al ingresar.</p>'
                    . '</div>';
            }

            $body .= '<p>Detalles del evento:</p>'
                . '<ul>'
                . '<li>Fecha: ' . ($eventData['date'] ?: 'Por confirmar') . '</li>'
                . (!empty($eventData['location']) ? '<li>Ubicación: ' . htmlspecialchars($eventData['location']) . '</li>' : '')
                . '</ul>';

            Mail::to($recipient)->send(
                new \App\Mail\GxStyledMail(
                    subject: 'Registro Recibido - ' . $eventData['name'],
                    heading: 'Hemos recibido tu registro',
                    body: $body,
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send tournament registration receipt email: ' . $e->getMessage());
        }
    }

    private function sendAdminRegistrationAlertMail(array $registrationData, array $eventData): void
    {
        $adminRecipient = config('mail.admin_registration_to', 'danielorellanaramirez@gmail.com');
        if (empty($adminRecipient)) {
            return;
        }

        $paymentOption = ($registrationData['payment_option'] ?? 'now') === 'now' ? 'Pagó al tiro' : 'Pagará después';
        $proofPath = $registrationData['proof_path'] ?? null;
        $proofPublicUrl = $proofPath ? asset('storage/' . ltrim($proofPath, '/')) : null;
        $reviewUrl = route('admin.events.show', $eventData['id']);

        $body = '<p>Se registró una nueva preinscripción para el torneo <strong>' . htmlspecialchars($eventData['name']) . '</strong>.</p>'
            . '<div class="highlight-box">'
            . '<p style="margin:0 0 8px; font-weight:700;">Datos del blader</p>'
            . '<ul style="margin:0; padding-left:18px;">'
            . '<li><strong>ID registro:</strong> #' . (int) ($registrationData['id'] ?? 0) . '</li>'
            . '<li><strong>Blader:</strong> ' . htmlspecialchars($registrationData['blader_name']) . '</li>'
            . '<li><strong>Nombre:</strong> ' . htmlspecialchars(trim(($registrationData['first_name'] ?? '') . ' ' . ($registrationData['last_name'] ?? '')) ?: 'No informado') . '</li>'
            . '<li><strong>Email:</strong> ' . htmlspecialchars($registrationData['email'] ?? 'No informado') . '</li>'
            . '<li><strong>WhatsApp:</strong> ' . htmlspecialchars($registrationData['whatsapp'] ?? 'No informado') . '</li>'
            . '<li><strong>Registrado en R.E.X:</strong> ' . (($registrationData['is_rex_registered'] ?? false) ? 'Sí' : 'No') . '</li>'
            . '<li><strong>Pago:</strong> ' . $paymentOption . '</li>'
            . '<li><strong>Fecha evento:</strong> ' . htmlspecialchars($eventData['date'] ?: 'Por confirmar') . '</li>'
            . '</ul>'
            . '</div>';

        if (!empty($proofPublicUrl)) {
            $body .= '<p>Comprobante adjunto en este correo. También puedes verlo aquí: <a href="'
                . htmlspecialchars($proofPublicUrl)
                . '" style="color:#E10600; font-weight:700;">Abrir comprobante</a>.</p>';
        } else {
            $body .= '<p><strong>Comprobante:</strong> No adjuntó (marcó pago posterior).</p>';
        }

        $body .= '<p>Pulsa el botón para revisar y validar la inscripción desde el panel admin.</p>';

        try {
            $mail = new \App\Mail\GxStyledMail(
                subject: 'Nueva Preinscripción - ' . $eventData['name'],
                heading: 'Nueva inscripción recibida',
                body: $body,
                ctaText: 'Revisar y validar inscripción',
                ctaUrl: $reviewUrl,
            );

            if (!empty($proofPath)) {
                $safeBlader = preg_replace('/[^A-Za-z0-9\-_]/', '_', $registrationData['blader_name'] ?? 'blader');
                $extension = pathinfo($proofPath, PATHINFO_EXTENSION) ?: 'jpg';
                $mail->attachFromStorageDisk('public', $proofPath, "comprobante-{$safeBlader}.{$extension}");
            }

            Mail::to($adminRecipient)->send($mail);
        } catch (\Throwable $e) {
            Log::error('Failed to send admin tournament registration alert email: ' . $e->getMessage());
        }
    }

    private function displayBladerName(?string $bladerName, ?string $firstName, ?string $lastName): string
    {
        if (!empty($bladerName)) {
            return $bladerName;
        }

        $fallback = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        return $fallback !== '' ? $fallback : 'Blader';
    }

    private function normalizeBladerName(string $value): string
    {
        $trimmed = trim($value);
        return preg_replace('/\s+/', ' ', $trimmed) ?: $trimmed;
    }

    private function normalizeEmail(string $value): string
    {
        return mb_strtolower(trim($value));
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
