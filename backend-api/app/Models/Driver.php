<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'license_number',
        'license_category',
        'photo_file_id',
        'status',
        'note',
    ];

    protected $casts = [
        'status' => DriverStatus::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photo()
    {
        return $this->belongsTo(File::class, 'photo_file_id');
    }

    public function waybills()
    {
        return $this->hasMany(Waybill::class);
    }
}

