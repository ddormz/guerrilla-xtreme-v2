<?php
namespace App\Models;

use App\Enums\MatchActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchAction extends Model
{
    public $timestamps = false;
    protected $fillable = ['match_id', 'side', 'action_type', 'created_by', 'created_at'];
    protected function casts(): array { return ['action_type' => MatchActionType::class, 'created_at' => 'datetime']; }

    public function match(): BelongsTo { return $this->belongsTo(LeagueMatch::class, 'match_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
