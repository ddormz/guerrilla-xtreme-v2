<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TeamMember extends Model
{
    protected $fillable = ['user_id', 'name', 'blader_name', 'role_title', 'photo_path', 'lock_chip_photo', 'instagram', 'tiktok', 'is_active', 'display_order', 'joined_date'];
    protected function casts(): array { return ['is_active' => 'boolean', 'joined_date' => 'date']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function getPhotoUrlAttribute(): ?string { return $this->photo_path ? asset('storage/' . $this->photo_path) : null; }
    public function getLockChipUrlAttribute(): ?string { return $this->lock_chip_photo ? asset('storage/' . $this->lock_chip_photo) : null; }
    public function scopeActive($query) { return $query->where('is_active', true)->orderBy('display_order'); }
}
