<?php

namespace App\Services;

use App\Models\Waybill;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function initialWaybill(Waybill $waybill)
    {
        return Pdf::loadView('pdf.waybill-initial', [
            'waybill' => $waybill->load(['driver', 'vehicle', 'workOrder', 'medicalInspections', 'technicalInspections']),
        ])->setPaper('a4');
    }

    public function finalWaybill(Waybill $waybill)
    {
        return Pdf::loadView('pdf.waybill-final', [
            'waybill' => $waybill->load(['driver', 'vehicle', 'workOrder', 'medicalInspections', 'technicalInspections', 'fuelLogs']),
        ])->setPaper('a4');
    }
}

