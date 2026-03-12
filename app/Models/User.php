<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'blader_name', 'email', 'role', 'phone',
        'password', 'password_temporary', 'avatar_path',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_temporary' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    // ----- Accessors -----
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? asset('storage/' . $this->avatar_path)
            : null;
    }

    // ----- Auth helpers -----
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isReferee(): bool
    {
        return $this->role->isReferee();
    }

    public function isMember(): bool
    {
        return $this->role->isMember();
    }

    // ----- Relationships -----
    public function leaguePlayer(): HasOne
    {
        return $this->hasOne(LeaguePlayer::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(FinanceWallet::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(RaffleReservation::class);
    }

    public function teamMember(): HasOne
    {
        return $this->hasOne(TeamMember::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }
}

