<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private const REPORTS = [
        'waybills',
        'mileage',
        'fuel',
        'driver-shifts',
        'medical-inspections',
        'technical-inspections',
        'vehicle-usage',
    ];

    public function show(string $type, Request $request)
    {
        abort_unless(in_array($type, self::REPORTS, true), 404);

        return response()->json([
            'type' => $type,
            'filters' => $request->query(),
            'columns' => $this->columns($type),
            'rows' => [],
            'message' => 'Report query service should fill rows from PostgreSQL.',
        ]);
    }

    public function export(string $type, Request $request)
    {
        abort_unless(in_array($type, self::REPORTS, true), 404);

        return response()->json([
            'type' => $type,
            'filters' => $request->query(),
            'message' => 'Excel export is planned through maatwebsite/excel.',
        ], 202);
    }

    private function columns(string $type): array
    {
        return match ($type) {
            'waybills' => ['number', 'date', 'driver', 'vehicle', 'route', 'status', 'mileage'],
            'mileage' => ['vehicle', 'period_start', 'period_end', 'mileage'],
            'fuel' => ['date', 'vehicle', 'driver', 'fuel_type', 'liters', 'cost', 'odometer'],
            'driver-shifts' => ['driver', 'date', 'shift', 'started_at', 'finished_at', 'status'],
            'medical-inspections' => ['date', 'type', 'driver', 'medic', 'status', 'reason'],
            'technical-inspections' => ['date', 'type', 'vehicle', 'mechanic', 'status', 'reason'],
            'vehicle-usage' => ['vehicle', 'shifts_count', 'mileage', 'fuel_liters', 'fuel_cost'],
        };
    }
}

