<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Waybill;
use App\Services\PdfService;
use Illuminate\Http\Request;

class WaybillController extends Controller
{
    public function index(Request $request)
    {
        $query = Waybill::query()
            ->with(['driver', 'vehicle', 'workOrder'])
            ->latest('date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->integer('driver_id'));
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->integer('vehicle_id'));
        }

        return response()->json(['items' => $query->paginate(20)]);
    }

    public function show(Waybill $waybill)
    {
        return response()->json([
            'waybill' => $waybill->load([
                'driver',
                'vehicle',
                'workOrder',
                'medicalInspections',
                'technicalInspections',
                'fuelLogs',
            ]),
        ]);
    }

    public function initialPdf(Waybill $waybill, PdfService $pdf)
    {
        return $pdf->initialWaybill($waybill)->stream("waybill-{$waybill->number}.pdf");
    }

    public function finalPdf(Waybill $waybill, PdfService $pdf)
    {
        return $pdf->finalWaybill($waybill)->stream("waybill-final-{$waybill->number}.pdf");
    }
}

