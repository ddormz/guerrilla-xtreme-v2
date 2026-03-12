<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleReservationNumber extends Model
{
    public $timestamps = false;
    protected $fillable = ['reservation_id', 'number'];
    public function reservation(): BelongsTo { return $this->belongsTo(RaffleReservation::class, 'reservation_id'); }
}
