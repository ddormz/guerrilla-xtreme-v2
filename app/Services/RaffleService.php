<?php

namespace App\Services;

use App\Enums\RaffleNumberStatus;
use App\Enums\RaffleStatus;
use App\Models\Raffle;
use App\Models\RaffleNumber;
use App\Models\RaffleReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RaffleService
{
    /**
     * Get active/published raffles for public listing.
     */
    public function getActiveRaffles()
    {
        return Raffle::where('status', RaffleStatus::Published)
            ->orWhere('status', RaffleStatus::Closed)
            ->orWhere('status', RaffleStatus::Drawn)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a reservation for a set of numbers.
     */
    public function reserveNumbers(Raffle $raffle, array $numbers, array $data, ?int $userId = null): RaffleReservation
    {
        $now = now();
        $withinSalesWindow = (!$raffle->sales_start_at || $raffle->sales_start_at <= $now)
            && (!$raffle->sales_end_at || $raffle->sales_end_at >= $now);

        if ($raffle->status !== RaffleStatus::Published || !$withinSalesWindow) {
            throw new \Exception('La rifa no se encuentra habilitada para nuevas reservas.');
        }

        return DB::transaction(function () use ($raffle, $numbers, $data, $userId) {
            $availableCount = RaffleNumber::where('raffle_id', $raffle->id)
                ->whereIn('number', $numbers)
                ->where('status', RaffleNumberStatus::Available)
                ->lockForUpdate()
                ->count();

            if ($availableCount !== count($numbers)) {
                throw new \Exception('Uno o más números ya no están disponibles.');
            }

            $reservation = RaffleReservation::create([
                'raffle_id' => $raffle->id,
                'user_id' => $userId,
                'buyer_name' => $data['buyer_name'],
                'blader_name' => $data['blader_name'] ?? '',
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => 'reserved',
                'total_amount' => count($numbers) * $raffle->ticket_price,
                'proof_path' => $data['proof_path'] ?? null,
            ]);

            RaffleNumber::where('raffle_id', $raffle->id)
                ->whereIn('number', $numbers)
                ->update(['status' => RaffleNumberStatus::Reserved]);

            foreach ($numbers as $number) {
                $reservation->numbers()->create(['number' => $number]);
            }

            return $reservation;
        });
    }

    /**
     * Auto-release expired reservations (24h).
     */
    public function releaseExpiredReservations(): int
    {
        $expired = RaffleReservation::where('status', 'reserved')
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->get();

        $count = 0;
        foreach ($expired as $reservation) {
            DB::transaction(function () use ($reservation) {
                RaffleNumber::where('raffle_id', $reservation->raffle_id)
                    ->whereIn('number', $reservation->numbers->pluck('number'))
                    ->update(['status' => RaffleNumberStatus::Available]);

                $reservation->update(['status' => 'cancelled']);
            });
            $count++;
        }

        return $count;
    }
}
