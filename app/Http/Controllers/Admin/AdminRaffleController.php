<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RaffleNumberStatus;
use App\Enums\RaffleStatus;
use App\Http\Controllers\Controller;
use App\Models\Raffle;
use App\Models\RaffleReservation;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminRaffleController extends Controller
{
    public function index(): Response
    {
        $raffles = Raffle::withCount('numbers')->orderBy('created_at', 'desc')->get();

        $mapReservation = fn (RaffleReservation $reservation) => [
            'id' => $reservation->id,
            'raffle_id' => $reservation->raffle_id,
            'raffle' => [
                'id' => $reservation->raffle?->id,
                'name' => $reservation->raffle?->name,
            ],
            'buyer_name' => $reservation->buyer_name,
            'blader_name' => $reservation->blader_name,
            'email' => $reservation->email,
            'phone' => $reservation->phone,
            'status' => $reservation->status,
            'total_amount' => $reservation->total_amount,
            'numbers' => $reservation->numbers->pluck('number')->values(),
            'proof_path' => $reservation->proof_path,
            'proof_url' => $reservation->proof_path ? Storage::url($reservation->proof_path) : null,
            'created_at' => $reservation->created_at?->toDateTimeString(),
            'validated_at' => $reservation->validated_at?->toDateTimeString(),
        ];

        $pendingReservations = RaffleReservation::where('status', 'reserved')
            ->with(['raffle', 'user', 'numbers'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map($mapReservation);

        $validatedReservations = RaffleReservation::where('status', 'confirmed')
            ->with(['raffle', 'user', 'numbers'])
            ->orderBy('validated_at', 'desc')
            ->limit(200)
            ->get()
            ->map($mapReservation);

        return Inertia::render('Admin/Raffles/Index', [
            'raffles' => $raffles,
            'pendingReservations' => $pendingReservations,
            'validatedReservations' => $validatedReservations,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Raffles/Create');
    }

    public function show(Raffle $raffle): Response
    {
        $raffle->load(['numbers.reservation', 'prizes']);

        $numbers = $raffle->numbers->map(function ($num) {
            $reservationProof = $num->reservation->first()?->proof_path;
            return [
                'id' => $num->id,
                'number' => $num->number,
                'status' => $num->status,
                'buyer_name' => $num->buyer_name,
                'blader_name' => $num->blader_name,
                'phone' => $num->phone,
                'email' => $num->email,
                'proof_url' => $num->proof_path ? Storage::url($num->proof_path) : ($reservationProof ? Storage::url($reservationProof) : null),
                'winner_photo_url' => $num->winner_photo ? Storage::url($num->winner_photo) : null,
                'prize_position' => $num->prize_position,
            ];
        });

        return Inertia::render('Admin/Raffles/Show', [
            'raffle' => $raffle,
            'mapped_numbers' => $numbers,
        ]);
    }

    public function edit(Raffle $raffle): Response
    {
        $raffle->load(['prizes', 'numbers']);

        return Inertia::render('Admin/Raffles/Edit', [
            'raffle' => $raffle,
        ]);
    }

    public function update(Request $request, Raffle $raffle)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'rules' => 'nullable|string',
            'ticket_price' => 'required|numeric|min:0',
            'sales_start_at' => 'nullable|date',
            'sales_end_at' => 'nullable|date|after_or_equal:sales_start_at',
            'draw_at' => 'nullable|date',
            'status' => 'required|string',
            'winner_photo' => 'nullable|image|max:10240',
            'winner_number' => 'nullable|integer',
            'bank_name' => 'nullable|string|max:120',
            'account_holder' => 'nullable|string|max:120',
            'account_number' => 'nullable|string|max:120',
            'account_type' => 'nullable|string|max:80',
            'account_email' => 'nullable|email|max:150',
            'payment_instructions' => 'nullable|string|max:1000',
        ]);

        if ($request->hasFile('winner_photo')) {
            $validated['winner_photo'] = $request->file('winner_photo')->store('raffles/winners', 'public');
        }

        if (!Schema::hasColumn('raffles', 'winner_number')) {
            unset($validated['winner_number']);
        }

        $raffle->update($validated);

        AuditLogger::log('update_raffle', 'Raffle', $raffle->id, ['name' => $raffle->name]);

        return back()->with('success', 'Rifa actualizada correctamente.');
    }

    public function destroy(Raffle $raffle)
    {
        $raffleId = $raffle->id;
        $raffleName = $raffle->name;
        $raffle->delete();

        AuditLogger::log('delete_raffle', 'Raffle', $raffleId, ['name' => $raffleName]);

        return redirect()->route('admin.raffles.index')->with('success', 'Rifa eliminada.');
    }

    public function storePrize(Request $request, Raffle $raffle)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position' => 'required|integer|min:1',
            'image' => 'nullable|image|max:10240',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('raffles/prizes', 'public');
        }

        $raffle->prizes()->create($validated);

        return back()->with('success', 'Premio añadido.');
    }

    public function deletePrize(\App\Models\RafflePrize $prize)
    {
        $prize->delete();

        return back()->with('success', 'Premio eliminado.');
    }

    public function manualAssign(Request $request, Raffle $raffle)
    {
        $validated = $request->validate([
            'numbers' => 'required|array|min:1',
            'buyer_name' => 'required|string|max:255',
            'blader_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'mark_as_paid' => 'boolean',
            'proof' => 'nullable|image|max:10240',
        ]);

        $status = $validated['mark_as_paid'] ? 'confirmed' : 'reserved';
        $numberStatus = $validated['mark_as_paid'] ? RaffleNumberStatus::Sold : RaffleNumberStatus::Reserved;

        DB::transaction(function () use ($raffle, $validated, $status, $numberStatus) {
            $proofPath = null;
            if (isset($validated['proof']) && $validated['proof']) {
                $proofPath = $validated['proof']->store('raffles/proofs', 'public');
            }

            $reservation = $raffle->reservations()->create([
                'buyer_name' => $validated['buyer_name'],
                'blader_name' => $validated['blader_name'] ?? '',
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'status' => $status,
                'total_amount' => count($validated['numbers']) * $raffle->ticket_price,
                'proof_path' => $proofPath,
                'validated_at' => $status === 'confirmed' ? now() : null,
            ]);

            foreach ($validated['numbers'] as $num) {
                $reservation->numbers()->create(['number' => $num]);
                $raffle->numbers()->where('number', $num)->update([
                    'status' => $numberStatus,
                    'buyer_name' => $validated['buyer_name'],
                    'blader_name' => $validated['blader_name'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'proof_path' => $proofPath,
                ]);
            }
        });

        return back()->with('success', 'Números asignados manualmente.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'rules' => 'nullable|string',
            'ticket_price' => 'required|numeric|min:0',
            'total_numbers' => 'required|integer|min:1',
            'sales_start_at' => 'nullable|date',
            'sales_end_at' => 'nullable|date|after_or_equal:sales_start_at',
            'draw_at' => 'nullable|date',
            'bank_name' => 'nullable|string|max:120',
            'account_holder' => 'nullable|string|max:120',
            'account_number' => 'nullable|string|max:120',
            'account_type' => 'nullable|string|max:80',
            'account_email' => 'nullable|email|max:150',
            'payment_instructions' => 'nullable|string|max:1000',
            'prizes' => 'nullable|array',
            'prizes.*.title' => 'required|string|max:255',
            'prizes.*.position' => 'required|integer|min:1',
            'prizes.*.description' => 'nullable|string',
            'prizes.*.image' => 'nullable|image|max:10240',
        ]);

        DB::transaction(function () use ($validated) {
            $raffleData = collect($validated)->except('prizes')->toArray();
            $raffleData['status'] = RaffleStatus::Draft;

            $raffle = Raffle::create($raffleData);

            if (!empty($validated['prizes'])) {
                foreach ($validated['prizes'] as $prizeData) {
                    $imagePath = null;
                    if (!empty($prizeData['image'])) {
                        $imagePath = $prizeData['image']->store('raffles/prizes', 'public');
                    }

                    $raffle->prizes()->create([
                        'title' => $prizeData['title'],
                        'description' => $prizeData['description'] ?? null,
                        'position' => $prizeData['position'],
                        'image_path' => $imagePath,
                    ]);
                }
            }

            AuditLogger::log('create_raffle', 'Raffle', $raffle->id, ['name' => $raffle->name]);
        });

        return redirect()->route('admin.raffles.index')->with('success', 'Rifa creada exitosamente con sus premios.');
    }

    public function publish(Raffle $raffle)
    {
        $raffle->update(['status' => RaffleStatus::Published]);

        AuditLogger::log('publish_raffle', 'Raffle', $raffle->id, ['name' => $raffle->name]);

        return back()->with('success', 'Rifa publicada.');
    }

    public function approveReservation(RaffleReservation $reservation)
    {
        $reservation->update([
            'status' => 'confirmed',
            'validated_at' => now(),
        ]);

        $numbers = $reservation->numbers->pluck('number');
        \App\Models\RaffleNumber::where('raffle_id', $reservation->raffle_id)
            ->whereIn('number', $numbers)
            ->update(['status' => RaffleNumberStatus::Sold]);

        AuditLogger::log('approve_raffle_reservation', 'RaffleReservation', $reservation->id, [
            'raffle_id' => $reservation->raffle_id,
        ]);

        // Send confirmation email
        try {
            $raffle = $reservation->raffle;
            $body = '<p>Tu reserva para la rifa <strong>' . htmlspecialchars($raffle->name ?? 'GX') . '</strong> ha sido <strong style="color:#22c55e;">confirmada</strong>.</p>'
                . '<div class="highlight-box"><p style="margin:0; font-weight:600;">Números reservados: ' . $numbers->join(', ') . '</p></div>'
                . '<p>¡Gracias por tu compra y mucha suerte!</p>';

            \Illuminate\Support\Facades\Mail::to($reservation->email)->send(
                new \App\Mail\GxStyledMail(
                    subject: 'Reserva Confirmada - ' . ($raffle->name ?? 'GX Rifa'),
                    heading: '¡Tu reserva ha sido confirmada!',
                    body: $body,
                )
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send reservation approval email: " . $e->getMessage());
        }

        return back()->with('success', 'Reserva confirmada.');
    }

    public function draw(Request $request, Raffle $raffle)
    {
        $validated = $request->validate([
            'winner_number' => 'required|integer',
            'winner_photo' => 'nullable|image|max:10240',
        ]);

        if ($request->hasFile('winner_photo')) {
            $validated['winner_photo'] = $request->file('winner_photo')->store('raffles/winners', 'public');
        }

        $updateData = [
            'status' => RaffleStatus::Drawn,
            'winner_photo' => $validated['winner_photo'] ?? $raffle->winner_photo,
        ];

        if (Schema::hasColumn('raffles', 'winner_number')) {
            $updateData['winner_number'] = $validated['winner_number'];
        }

        $raffle->update($updateData);

        AuditLogger::log('draw_raffle', 'Raffle', $raffle->id, [
            'winner_number' => $validated['winner_number'],
        ]);

        return back()->with('success', 'Número ganador registrado.');
    }

    public function clearSingleNumber(Raffle $raffle, $number)
    {
        $raffleNumber = $raffle->numbers()->where('number', $number)->firstOrFail();

        $raffleNumber->update([
            'status' => RaffleNumberStatus::Available,
            'buyer_name' => null,
            'blader_name' => null,
            'phone' => null,
            'email' => null,
            'proof_path' => null,
            'winner_photo' => null,
            'prize_position' => null,
        ]);

        return back()->with('success', "Número $number liberado exitosamente.");
    }

    public function clearAllNumbers(Raffle $raffle)
    {
        $raffle->numbers()->update([
            'status' => RaffleNumberStatus::Available,
            'buyer_name' => null,
            'blader_name' => null,
            'phone' => null,
            'email' => null,
            'proof_path' => null,
            'winner_photo' => null,
            'prize_position' => null,
        ]);

        $raffle->reservations()->update(['status' => 'cancelled']);

        return back()->with('success', 'Todos los números han sido liberados.');
    }

    public function uploadManualProof(Request $request, Raffle $raffle, $number)
    {
        $validated = $request->validate([
            'proof' => 'required|image|max:10240',
        ]);

        $raffleNumber = $raffle->numbers()->where('number', $number)->firstOrFail();
        $path = $request->file('proof')->store('raffles/proofs', 'public');

        $raffleNumber->update(['proof_path' => $path]);

        return back()->with('success', 'Comprobante subido correctamente.');
    }

    public function markWinner(Request $request, Raffle $raffle, $number)
    {
        $validated = $request->validate([
            'prize_position' => 'required|integer|min:1',
            'winner_photo' => 'nullable|image|max:10240',
        ]);

        $raffleNumber = $raffle->numbers()->where('number', $number)->firstOrFail();

        $data = [
            'status' => RaffleNumberStatus::Winner,
            'prize_position' => $validated['prize_position'],
        ];

        if ($request->hasFile('winner_photo')) {
            $data['winner_photo'] = $request->file('winner_photo')->store('raffles/winners', 'public');
        }

        $raffleNumber->update($data);

        return back()->with('success', 'Número marcado como ganador.');
    }

    public function uploadWinnerPhoto(Request $request, Raffle $raffle, $number)
    {
        $validated = $request->validate([
            'winner_photo' => 'required|image|max:10240',
        ]);

        $raffleNumber = $raffle->numbers()->where('number', $number)->firstOrFail();
        $path = $request->file('winner_photo')->store('raffles/winners', 'public');

        $raffleNumber->update(['winner_photo' => $path]);

        return back()->with('success', 'Foto del ganador subida correctamente.');
    }
}
