<?php
namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeagueEvent extends Model
{
    protected $fillable = [
        'season_id',
        'name',
        'event_date',
        'status',
        'event_type',
        'is_live',
        'description',
        'rules',
        'location',
        'time',
        'prizes',
        'registration_cost',
        'show_on_index',
        'matches_locked',
        'flyer_image_path',
        'bank_name',
        'account_type',
        'account_holder',
        'account_number',
        'account_email',
        'payment_instructions',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'event_date' => 'datetime',
            'is_live' => 'boolean',
            'show_on_index' => 'boolean',
            'matches_locked' => 'boolean',
            'registration_cost' => 'decimal:2',
        ];
    }

    public function season(): BelongsTo { return $this->belongsTo(LeagueSeason::class, 'season_id'); }
    public function matches(): HasMany { return $this->hasMany(LeagueMatch::class, 'event_id'); }
    public function attendance(): HasMany { return $this->hasMany(LeagueAttendance::class, 'event_id'); }
    public function participants(): HasMany { return $this->hasMany(TournamentParticipant::class, 'event_id'); }
    public function registrations(): HasMany { return $this->hasMany(TournamentRegistration::class, 'event_id'); }

    public function scopeLive($query) { return $query->where('is_live', true); }
    public function isTournament(): bool { return $this->event_type === EventType::Torneo; }
}
