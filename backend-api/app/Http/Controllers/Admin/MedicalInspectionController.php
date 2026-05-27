<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicalInspection;
use App\Services\InspectionService;
use Illuminate\Http\Request;

class MedicalInspectionController extends Controller
{
    public function __construct(private readonly InspectionService $inspections)
    {
    }

    public function index(Request $request)
    {
        $query = MedicalInspection::query()
            ->with(['driver', 'waybill', 'medic'])
            ->latest('requested_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return response()->json(['items' => $query->paginate(20)]);
    }

    public function approve(MedicalInspection $medicalInspection, Request $request)
    {
        return response()->json([
            'inspection' => $this->inspections->decideMedical($medicalInspection, $request->user(), true),
        ]);
    }

    public function reject(MedicalInspection $medicalInspection, Request $request)
    {
        $payload = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        return response()->json([
            'inspection' => $this->inspections->decideMedical($medicalInspection, $request->user(), false, $payload['reason']),
        ]);
    }
}

