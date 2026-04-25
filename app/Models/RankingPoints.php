<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingPoints extends Model
{
    protected $table = 'ranking_points';

    protected $fillable = [
        'player_id',
        'points_for',
        'points_against',
        'differential',
        'wins',
        'losses',
        'xtremes',
        'matches_played',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(LeaguePlayer::class, 'player_id');
    }
}
