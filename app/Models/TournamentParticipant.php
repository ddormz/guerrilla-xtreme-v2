<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentParticipant extends Model
{
    protected $fillable = ['event_id', 'player_id', 'user_id', 'guest_name', 'blader_name', 'seed', 'eliminated', 'status', 'bye_count'];
    protected function casts(): array { return ['eliminated' => 'boolean']; }

    public function event(): BelongsTo { return $this->belongsTo(LeagueEvent::class, 'event_id'); }
    public function player(): BelongsTo { return $this->belongsTo(LeaguePlayer::class, 'player_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function getDisplayNameAttribute(): string { return $this->blader_name ?: $this->guest_name ?: 'Unknown'; }
}
