<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case OnLine = 'on_line';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';
}

