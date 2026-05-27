<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('driver.{driverId}', function ($user, int $driverId) {
    return $user->role->value === 'driver' && $user->driver?->id === $driverId;
});

Broadcast::channel('admin.dashboard', function ($user) {
    return in_array($user->role->value, ['admin', 'dispatcher', 'medic', 'mechanic'], true);
});

Broadcast::channel('admin.map', function ($user) {
    return in_array($user->role->value, ['admin', 'dispatcher'], true);
});

Broadcast::channel('medical.queue', function ($user) {
    return in_array($user->role->value, ['admin', 'medic'], true);
});

Broadcast::channel('technical.queue', function ($user) {
    return in_array($user->role->value, ['admin', 'mechanic'], true);
});

