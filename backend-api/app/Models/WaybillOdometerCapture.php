<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaybillOdometerCapture extends Model
{
    protected $fillable = [
        'waybill_id',
        'capture_type',
        'file_id',
        'ocr_raw_text',
        'ocr_candidates',
        'ocr_value',
        'ocr_confidence',
        'confirmed_value',
        'confirmed_by_user_id',
        'confirmed_at',
        'recognition_status',
        'recognition_error',
    ];

    protected $casts = [
        'ocr_raw_text' => 'array',
        'ocr_candidates' => 'array',
        'ocr_value' => 'integer',
        'ocr_confidence' => 'decimal:4',
        'confirmed_value' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    public function waybill()
    {
        return $this->belongsTo(Waybill::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }
}
