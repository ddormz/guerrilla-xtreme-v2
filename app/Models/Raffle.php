<?php

namespace App\Models;

use App\Enums\RaffleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Raffle extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'rules',
        'ticket_price',
        'total_numbers',
        'status',
        'sales_start_at',
        'sales_end_at',
        'draw_at',
        'winner_photo',
        'winner_number',
        'bank_name',
        'account_holder',
        'account_number',
        'account_type',
        'account_email',
        'payment_instructions',
    ];

    protected function casts(): array
    {
        return [
            'status' => RaffleStatus::class,
            'sales_start_at' => 'datetime',
            'sales_end_at' => 'datetime',
            'draw_at' => 'datetime',
            'ticket_price' => 'integer',
            'total_numbers' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Raffle $raffle) {
            if (empty($raffle->slug)) {
                $raffle->slug = Str::slug($raffle->name) . '-' . Str::random(6);
            }
        });
    }

    public function numbers(): HasMany { return $this->hasMany(RaffleNumber::class); }
    public function prizes(): HasMany { return $this->hasMany(RafflePrize::class)->orderBy('position'); }
    public function reservations(): HasMany { return $this->hasMany(RaffleReservation::class); }
}

