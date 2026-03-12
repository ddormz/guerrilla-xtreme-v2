<?php

namespace App\Enums;

enum UserRole: string
{
    case Usuario = 'usuario';
    case Miembro = 'miembro';
    case ArbitroGx = 'arbitro_gx';
    case ArbitroExterno = 'arbitro_externo';
    case MiembroGx = 'miembro_gx';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Usuario => 'Usuario',
            self::Miembro => 'Miembro',
            self::ArbitroGx => 'Árbitro GX',
            self::ArbitroExterno => 'Árbitro Externo',
            self::MiembroGx => 'Miembro GX',
            self::Admin => 'Administrador',
        };
    }

    public function isReferee(): bool
    {
        return in_array($this, [self::ArbitroGx, self::ArbitroExterno, self::Admin]);
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isMember(): bool
    {
        return in_array($this, [self::Miembro, self::MiembroGx, self::ArbitroGx, self::Admin]);
    }
}
