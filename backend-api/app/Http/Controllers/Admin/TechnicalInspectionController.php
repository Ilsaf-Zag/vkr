<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TechnicalInspection;
use App\Services\InspectionService;
use Illuminate\Http\Request;

class TechnicalInspectionController extends Controller
{
    public function __construct(private readonly InspectionService $inspections)
    {
    }

    public function index(Request $request)
    {
        $query = TechnicalInspection::query()
            ->with(['driver', 'vehicle', 'waybill', 'mechanic'])
            ->latest('requested_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        return response()->json(['items' => $query->paginate(20)]);
    }

    public function approve(TechnicalInspection $technicalInspection, Request $request)
    {
        return response()->json([
            'inspection' => $this->inspections->decideTechnical($technicalInspection, $request->user(), true),
        ]);
    }

    public function reject(TechnicalInspection $technicalInspection, Request $request)
    {
        $payload = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        return response()->json([
            'inspection' => $this->inspections->decideTechnical($technicalInspection, $request->user(), false, $payload['reason']),
        ]);
    }
}
