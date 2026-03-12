<?php
namespace App\Models;

use App\Enums\FinanceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceWallet extends Model
{
    protected $fillable = ['user_id', 'name', 'balance'];
    protected function casts(): array { return ['balance' => 'decimal:2']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function movements(): HasMany { return $this->hasMany(FinanceMovement::class, 'wallet_id'); }
}
