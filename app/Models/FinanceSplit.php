<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FinanceSplit extends Model
{
    public $timestamps = false;
    protected $table = 'finance_split';
    protected $fillable = ['movement_id', 'user_id', 'share'];
    protected function casts(): array { return ['share' => 'decimal:2']; }
    public function movement(): BelongsTo { return $this->belongsTo(FinanceMovement::class, 'movement_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
