<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeagueMatch extends Model
{
    protected $table = 'league_matches';

    protected $fillable = [
        'event_id', 'round_no', 'player_a_id', 'player_b_id', 'best_of',
        'score_a', 'score_b', 'winner_id', 'xtreme_a', 'xtreme_b',
        'spin_a', 'spin_b', 'over_a', 'over_b', 'burst_a', 'burst_b',
        'strikes_a', 'strikes_b', 'concluded', 'is_recovery', 'referee_user_id',
        'group_id', 'game_no',
    ];

    protected function casts(): array
    {
        return [
            'concluded' => 'boolean',
            'is_recovery' => 'boolean',
        ];
    }

    public function event(): BelongsTo { return $this->belongsTo(LeagueEvent::class, 'event_id'); }
    public function playerA(): BelongsTo { return $this->belongsTo(LeaguePlayer::class, 'player_a_id'); }
    public function playerB(): BelongsTo { return $this->belongsTo(LeaguePlayer::class, 'player_b_id'); }
    public function player_a(): BelongsTo { return $this->playerA(); }
    public function player_b(): BelongsTo { return $this->playerB(); }
    public function winner(): BelongsTo { return $this->belongsTo(LeaguePlayer::class, 'winner_id'); }
    public function referee(): BelongsTo { return $this->belongsTo(User::class, 'referee_user_id'); }
    public function actions(): HasMany { return $this->hasMany(MatchAction::class, 'match_id')->orderBy('created_at'); }

    public function scopePending($query) { return $query->where('concluded', false); }
    public function scopeConcluded($query) { return $query->where('concluded', true); }
}
