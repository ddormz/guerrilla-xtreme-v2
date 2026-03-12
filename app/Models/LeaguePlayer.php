<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaguePlayer extends Model
{
    protected $fillable = ['user_id', 'parent_id', 'real_name', 'blader_name', 'avatar_path', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function parent(): BelongsTo { return $this->belongsTo(LeaguePlayer::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(LeaguePlayer::class, 'parent_id'); }
    public function seasons(): BelongsToMany { return $this->belongsToMany(LeagueSeason::class, 'league_roster', 'player_id', 'season_id')->withPivot('seed'); }
    public function points(): HasMany { return $this->hasMany(LeaguePoints::class, 'player_id'); }

    public function getAvatarUrlAttribute(): ?string
    {
        $path = $this->avatar_path ?? $this->user?->avatar_path;
        return $path ? asset('storage/' . $path) : null;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->blader_name ?: $this->real_name;
    }
}
