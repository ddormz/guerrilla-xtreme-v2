<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaguePoints extends Model
{
    protected $fillable = ['season_id', 'player_id', 'points', 'wins', 'losses', 'xtremes', 'spins', 'overs', 'bursts'];

    public function season(): BelongsTo { return $this->belongsTo(LeagueSeason::class, 'season_id'); }
    public function player(): BelongsTo { return $this->belongsTo(LeaguePlayer::class, 'player_id'); }
}
