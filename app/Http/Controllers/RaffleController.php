<?php

namespace App\Http\Controllers;

use App\Enums\RaffleNumberStatus;
use App\Enums\RaffleStatus;
use App\Models\Raffle;
use App\Services\RaffleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RaffleController extends Controller
{
    public function __construct(
        protected RaffleService $raffleService
    ) {}

    public function index(): Response
    {
        $raffles = $this->raffleService->getActiveRaffles()->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'slug' => $r->slug,
            'description' => $r->description,
            'ticket_price' => $r->ticket_price,
            'total_numbers' => $r->total_numbers,
            'status' => $r->status->value,
            'status_label' => $r->status->label(),
            'draw_at' => $r->draw_at?->format('d/m/Y H:i'),
        ]);

        return Inertia::render('Raffles/Index', [
            'raffles' => $raffles,
        ]);
    }

    public function show(Raffle $raffle): Response
    {
        $raffle->load(['numbers', 'prizes', 'reservations.numbers']);

        $myNumbers = collect();
        if (auth()->check()) {
            $myNumbers = $raffle->reservations
                ->where('user_id', auth()->id())
                ->whereIn('status', ['reserved', 'confirmed'])
                ->flatMap(fn ($reservation) => $reservation->numbers->pluck('number'))
                ->unique();
        }

        $now = now();
        $withinSalesWindow = (!$raffle->sales_start_at || $raffle->sales_start_at <= $now)
            && (!$raffle->sales_end_at || $raffle->sales_end_at >= $now);

        $canReserve = $raffle->status === RaffleStatus::Published && $withinSalesWindow;
        $reservationBlockedMessage = match ($raffle->status) {
            RaffleStatus::Drawn => 'Esta rifa ya fue sorteada. No se pueden seleccionar números.',
            RaffleStatus::Cancelled => 'Esta rifa fue cancelada y no admite reservas.',
            RaffleStatus::Closed => 'Las reservas para esta rifa ya se encuentran cerradas.',
            RaffleStatus::Draft => 'Esta rifa aún no está publicada.',
            default => $withinSalesWindow
                ? 'Esta rifa no acepta más reservas.'
                : 'Las reservas no están disponibles en este momento para esta rifa.',
        };

        $prizesByPosition = $raffle->prizes->keyBy('position');

        $winners = $raffle->numbers
            ->filter(fn ($n) => $n->status === RaffleNumberStatus::Winner)
            ->sortBy('prize_position')
            ->values()
            ->map(function ($n) use ($prizesByPosition) {
                $prize = $prizesByPosition->get($n->prize_position);

                return [
                    'number' => $n->number,
                    'blader_name' => $n->blader_name,
                    'buyer_name' => $n->buyer_name,
                    'prize_position' => $n->prize_position,
                    'prize_title' => $prize?->title,
                    'winner_photo_url' => $n->winner_photo ? asset('storage/' . $n->winner_photo) : null,
                ];
            });

        return Inertia::render('Raffles/Show', [
            'raffle' => [
                'id' => $raffle->id,
                'name' => $raffle->name,
                'slug' => $raffle->slug,
                'description' => $raffle->description,
                'rules' => $raffle->rules,
                'ticket_price' => $raffle->ticket_price,
                'total_numbers' => $raffle->total_numbers,
                'status' => $raffle->status->value,
                'status_label' => $raffle->status->label(),
                'draw_at' => $raffle->draw_at?->format('d/m/Y H:i'),
                'can_reserve' => $canReserve,
                'reservation_blocked_message' => $canReserve ? null : $reservationBlockedMessage,
                'numbers' => $raffle->numbers->map(fn ($n) => [
                    'number' => $n->number,
                    'status' => $n->status->value,
                    'blader_name' => $n->blader_name,
                    'is_mine' => $myNumbers->contains($n->number),
                ]),
                'prizes' => $raffle->prizes->map(fn ($p) => [
                    'position' => $p->position,
                    'title' => $p->title,
                    'description' => $p->description,
                    'image_url' => $p->image_path ? asset('storage/' . $p->image_path) : null,
                ]),
                'winner_number' => $raffle->numbers
                    ->firstWhere('status', RaffleNumberStatus::Winner)?->number,
                'winners' => $winners,
            ],
            'paymentInfo' => [
                'Banco' => $raffle->bank_name ?: 'BancoEstado',
                'Titular' => $raffle->account_holder ?: 'Guerrilla Xtrem',
                'Número de Cuenta' => $raffle->account_number ?: 'Por confirmar',
                'Tipo de Cuenta' => $raffle->account_type ?: 'Por confirmar',
                'Correo' => $raffle->account_email ?: 'Por confirmar',
                'Instrucciones adicionales' => $raffle->payment_instructions ?: 'Enviar comprobante y esperar validación.',
            ],
        ]);
    }

    public function reserve(Request $request, Raffle $raffle)
    {
        if ($raffle->status === RaffleStatus::Drawn) {
            return back()->with('error', 'Esta rifa ya fue sorteada. No se pueden seleccionar números.');
        }

        $now = now();
        $withinSalesWindow = (!$raffle->sales_start_at || $raffle->sales_start_at <= $now)
            && (!$raffle->sales_end_at || $raffle->sales_end_at >= $now);

        if ($raffle->status !== RaffleStatus::Published || !$withinSalesWindow) {
            return back()->with('error', 'Esta rifa no acepta más reservas.');
        }

        $validated = $request->validate([
            'numbers' => 'required|array|min:1',
            'numbers.*' => 'integer',
            'buyer_name' => 'required|string|max:150',
            'blader_name' => 'nullable|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:50',
            'proof' => 'required|file|image|max:5120',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('raffles/proofs', 'public');
        }

        $validated['proof_path'] = $proofPath;

        try {
            $this->raffleService->reserveNumbers(
                $raffle,
                $validated['numbers'],
                $validated,
                auth()->id()
            );

            return redirect()->route('raffles.show', $raffle)
                ->with('success', 'Reserva realizada con éxito. Tienes 24 horas para enviar el comprobante.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
