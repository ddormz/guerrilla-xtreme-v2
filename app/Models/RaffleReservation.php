<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaffleReservation extends Model
{
    protected $fillable = [
        'raffle_id', 'user_id', 'buyer_name', 'blader_name', 'email',
        'phone', 'status', 'total_amount', 'proof_path', 'validated_at',
    ];
    protected function casts(): array { return ['validated_at' => 'datetime', 'total_amount' => 'integer']; }

    public function raffle(): BelongsTo { return $this->belongsTo(Raffle::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function numbers(): HasMany { return $this->hasMany(RaffleReservationNumber::class, 'reservation_id'); }
}
