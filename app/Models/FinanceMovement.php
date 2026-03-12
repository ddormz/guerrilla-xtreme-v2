<?php
namespace App\Models;

use App\Enums\FinanceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceMovement extends Model
{
    protected $fillable = ['wallet_id', 'category_id', 'type', 'amount', 'description', 'created_by'];
    protected function casts(): array { return ['type' => FinanceType::class, 'amount' => 'decimal:2']; }

    public function wallet(): BelongsTo { return $this->belongsTo(FinanceWallet::class, 'wallet_id'); }
    public function category(): BelongsTo { return $this->belongsTo(FinanceCategory::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function splits(): HasMany { return $this->hasMany(FinanceSplit::class, 'movement_id'); }
}
