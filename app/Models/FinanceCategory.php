<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinanceCategory extends Model
{
    protected $fillable = ['name'];

    public function movements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FinanceMovement::class, 'category_id');
    }
}
