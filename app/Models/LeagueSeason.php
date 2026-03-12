<?php
namespace App\Models;

use App\Enums\SeasonStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeagueSeason extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'prize_1', 'prize_2', 'prize_3', 'precio_inscripcion', 'status'];
    protected function casts(): array { return ['status' => SeasonStatus::class, 'start_date' => 'date', 'end_date' => 'date']; }

    public function players(): BelongsToMany { return $this->belongsToMany(LeaguePlayer::class, 'league_roster', 'season_id', 'player_id')->withPivot('seed'); }
    public function events(): HasMany { return $this->hasMany(LeagueEvent::class, 'season_id'); }
    public function points(): HasMany { return $this->hasMany(LeaguePoints::class, 'season_id'); }

    public function scopeActive($query) { return $query->where('status', SeasonStatus::EnCurso); }
}
