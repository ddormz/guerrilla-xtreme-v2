<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueAttendance extends Model
{
    public $timestamps = false;
    protected $table = 'league_attendance';
    protected $fillable = ['event_id', 'player_id', 'present'];
    protected function casts(): array { return ['present' => 'boolean', 'paid' => 'boolean']; }

    public function event(): BelongsTo { return $this->belongsTo(LeagueEvent::class, 'event_id'); }
    public function player(): BelongsTo { return $this->belongsTo(LeaguePlayer::class, 'player_id'); }
}

