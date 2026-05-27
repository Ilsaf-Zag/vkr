<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Dispatcher = 'dispatcher';
    case Medic = 'medic';
    case Mechanic = 'mechanic';
    case Driver = 'driver';

    public function isEmployee(): bool
    {
        return $this !== self::Driver;
    }
}

