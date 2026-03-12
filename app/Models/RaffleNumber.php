<?php
namespace App\Models;

use App\Enums\RaffleNumberStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleNumber extends Model
{
    protected $fillable = ['raffle_id', 'number', 'status', 'buyer_name', 'blader_name', 'phone', 'email', 'proof_path', 'winner_photo', 'prize_position'];
    protected function casts(): array { return ['status' => RaffleNumberStatus::class, 'number' => 'integer']; }
    public function raffle(): BelongsTo { return $this->belongsTo(Raffle::class); }

    public function reservations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            RaffleReservation::class,
            'raffle_reservation_numbers',
            'number',
            'reservation_id',
            'number',
            'id'
        )->where('raffle_id', $this->raffle_id);
    }

    public function reservation()
    {
        return $this->reservations()->latest()->limit(1);
    }
}

