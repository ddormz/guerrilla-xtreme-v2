<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'player_id',
        'is_internal',
        'blader_name',
        'whatsapp',
        'email',
        'first_name',
        'last_name',
        'birth_date',
        'is_rex_registered',
        'generated_user_id',
        'proof_path',
        'status',
        'payment_date',
        'validated_at',
        'validation_notes',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'birth_date' => 'date',
        'is_rex_registered' => 'boolean',
        'payment_date' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(LeagueEvent::class, 'event_id');
    }

    public function player()
    {
        return $this->belongsTo(LeaguePlayer::class, 'player_id');
    }

    public function generatedUser()
    {
        return $this->belongsTo(User::class, 'generated_user_id');
    }
}
