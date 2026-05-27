<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'type',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];
}

