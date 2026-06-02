<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\FuelLog;
use App\Models\MedicalInspection;
use App\Models\TechnicalInspection;
use App\Models\Vehicle;
use App\Models\Waybill;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        return response()->json($this->build($type, $request));
    }

    public function export(string $type, Request $request)
    {
        abort_unless(in_array($type, self::REPORTS, true), 404);

        $report = $this->build($type, $request);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($this->reportTitle($type), 0, 31));

        foreach ($report['columns'] as $index => $column) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . '1', $report['column_labels'][$column] ?? $column);
        }

        foreach ($report['rows'] as $rowIndex => $row) {
            foreach ($report['columns'] as $columnIndex => $column) {
                $cell = Coordinate::stringFromColumnIndex($columnIndex + 1) . ($rowIndex + 2);
                $sheet->setCellValue($cell, $row[$column] ?? '');
            }
        }

        foreach (range(1, count($report['columns'])) as $columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }

        $file = tempnam(sys_get_temp_dir(), 'azyk-report-');
        (new Xlsx($spreadsheet))->save($file);

        return response()->download($file, "{$type}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function build(string $type, Request $request): array
    {
        $rows = match ($type) {
            'waybills' => $this->waybills($request),
            'mileage' => $this->mileage($request),
            'fuel' => $this->fuel($request),
            'driver-shifts' => $this->driverShifts($request),
            'medical-inspections' => $this->medicalInspections($request),
            'technical-inspections' => $this->technicalInspections($request),
            'vehicle-usage' => $this->vehicleUsage($request),
        };

        return [
            'type' => $type,
            'filters' => $request->query(),
            'columns' => $this->columns($type),
            'column_labels' => $this->columnLabels($type),
            'rows' => $rows,
        ];
    }

    private function reportTitle(string $type): string
    {
        return match ($type) {
            'waybills' => 'Путевые листы',
            'mileage' => 'Пробег автомобилей',
            'fuel' => 'Заправки',
            'driver-shifts' => 'Смены водителей',
            'medical-inspections' => 'Медосмотры',
            'technical-inspections' => 'Техосмотры',
            'vehicle-usage' => 'Использование автомобилей',
        };
    }

    private function columns(string $type): array
    {
        return match ($type) {
            'waybills' => ['number', 'date', 'driver', 'vehicle', 'route', 'status', 'mileage'],
            'mileage' => ['vehicle', 'plate_number', 'period_start', 'period_end', 'mileage'],
            'fuel' => ['date', 'vehicle', 'driver', 'fuel_type', 'liters', 'cost'],
            'driver-shifts' => ['driver', 'date', 'shift', 'started_at', 'finished_at', 'status'],
            'medical-inspections' => ['date', 'type', 'driver', 'medic', 'status', 'reason'],
            'technical-inspections' => ['date', 'type', 'vehicle', 'mechanic', 'status', 'reason'],
            'vehicle-usage' => ['vehicle', 'plate_number', 'shifts_count', 'mileage', 'fuel_liters', 'fuel_cost'],
        };
    }

    private function columnLabels(string $type): array
    {
        return [
            'number' => 'Номер',
            'date' => 'Дата',
            'driver' => 'Водитель',
            'vehicle' => 'Автомобиль',
            'route' => 'Маршрут',
            'status' => 'Статус',
            'mileage' => 'Пробег, км',
            'plate_number' => 'Госномер',
            'period_start' => 'Начало периода',
            'period_end' => 'Конец периода',
            'fuel_type' => 'Тип топлива',
            'liters' => 'Литры',
            'cost' => 'Стоимость',
            'shift' => 'Смена',
            'started_at' => 'Начало',
            'finished_at' => 'Завершение',
            'type' => 'Тип',
            'medic' => 'Медик',
            'mechanic' => 'Механик',
            'reason' => 'Причина',
            'shifts_count' => 'Смен',
            'fuel_liters' => 'Топливо, л',
            'fuel_cost' => 'Стоимость топлива',
        ];
    }

    private function waybills(Request $request): array
    {
        return Waybill::query()
            ->with(['driver', 'vehicle'])
            ->tap(fn (Builder $query) => $this->applyDateRange($query, $request, 'date'))
            ->latest('date')
            ->limit(500)
            ->get()
            ->map(fn (Waybill $waybill) => [
                'number' => $waybill->number,
                'date' => $waybill->date?->toDateString(),
                'driver' => $waybill->driver?->full_name,
                'vehicle' => $waybill->vehicle?->plate_number,
                'route' => $waybill->route_name,
                'status' => $this->label($waybill->status?->value ?? $waybill->status),
                'mileage' => $waybill->odometer_end && $waybill->odometer_start
                    ? $waybill->odometer_end - $waybill->odometer_start
                    : null,
            ])
            ->all();
    }

    private function mileage(Request $request): array
    {
        return Waybill::query()
            ->with('vehicle')
            ->whereNotNull('odometer_start')
            ->whereNotNull('odometer_end')
            ->tap(fn (Builder $query) => $this->applyDateRange($query, $request, 'date'))
            ->get()
            ->groupBy('vehicle_id')
            ->map(function ($items) use ($request) {
                $vehicle = $items->first()->vehicle;

                return [
                    'vehicle' => trim(($vehicle?->brand ?? '').' '.($vehicle?->model ?? '')),
                    'plate_number' => $vehicle?->plate_number,
                    'period_start' => $request->query('date_from'),
                    'period_end' => $request->query('date_to'),
                    'mileage' => $items->sum(fn (Waybill $waybill) => $waybill->odometer_end - $waybill->odometer_start),
                ];
            })
            ->values()
            ->all();
    }

    private function fuel(Request $request): array
    {
        return FuelLog::query()
            ->with(['vehicle', 'driver'])
            ->tap(fn (Builder $query) => $this->applyDateRange($query, $request, 'fueled_at'))
            ->latest('fueled_at')
            ->limit(500)
            ->get()
            ->map(fn (FuelLog $fuelLog) => [
                'date' => $fuelLog->fueled_at?->format('Y-m-d H:i'),
                'vehicle' => $fuelLog->vehicle?->plate_number,
                'driver' => $fuelLog->driver?->full_name,
                'fuel_type' => $this->label($fuelLog->fuel_type?->value ?? $fuelLog->fuel_type),
                'liters' => $fuelLog->liters,
                'cost' => $fuelLog->cost,
            ])
            ->all();
    }

    private function driverShifts(Request $request): array
    {
        return WorkOrder::query()
            ->with(['driver', 'waybill'])
            ->tap(fn (Builder $query) => $this->applyDateRange($query, $request, 'date'))
            ->latest('date')
            ->limit(500)
            ->get()
            ->map(fn (WorkOrder $workOrder) => [
                'driver' => $workOrder->driver?->full_name,
                'date' => $workOrder->date?->toDateString(),
                'shift' => $workOrder->shift,
                'started_at' => $workOrder->waybill?->shift_started_at?->format('Y-m-d H:i'),
                'finished_at' => $workOrder->waybill?->shift_finished_at?->format('Y-m-d H:i'),
                'status' => $this->label($workOrder->status?->value ?? $workOrder->status),
            ])
            ->all();
    }

    private function medicalInspections(Request $request): array
    {
        return MedicalInspection::query()
            ->with(['driver', 'medic'])
            ->tap(fn (Builder $query) => $this->applyDateRange($query, $request, 'requested_at'))
            ->latest('requested_at')
            ->limit(500)
            ->get()
            ->map(fn (MedicalInspection $inspection) => [
                'date' => $inspection->requested_at?->format('Y-m-d H:i'),
                'type' => $this->label($inspection->type?->value ?? $inspection->type),
                'driver' => $inspection->driver?->full_name,
                'medic' => $inspection->medic?->full_name,
                'status' => $this->label($inspection->status?->value ?? $inspection->status),
                'reason' => $inspection->rejection_reason,
            ])
            ->all();
    }

    private function technicalInspections(Request $request): array
    {
        return TechnicalInspection::query()
            ->with(['vehicle', 'mechanic'])
            ->tap(fn (Builder $query) => $this->applyDateRange($query, $request, 'requested_at'))
            ->latest('requested_at')
            ->limit(500)
            ->get()
            ->map(fn (TechnicalInspection $inspection) => [
                'date' => $inspection->requested_at?->format('Y-m-d H:i'),
                'type' => $this->label($inspection->type?->value ?? $inspection->type),
                'vehicle' => $inspection->vehicle?->plate_number,
                'mechanic' => $inspection->mechanic?->full_name,
                'status' => $this->label($inspection->status?->value ?? $inspection->status),
                'reason' => $inspection->rejection_reason,
            ])
            ->all();
    }

    private function vehicleUsage(Request $request): array
    {
        return Vehicle::query()
            ->with(['waybills.fuelLogs'])
            ->get()
            ->map(function (Vehicle $vehicle) use ($request) {
                $waybills = $vehicle->waybills
                    ->filter(fn (Waybill $waybill) => $this->inDateRange($waybill->date?->toDateString(), $request));

                return [
                    'vehicle' => trim($vehicle->brand.' '.$vehicle->model),
                    'plate_number' => $vehicle->plate_number,
                    'shifts_count' => $waybills->count(),
                    'mileage' => $waybills->sum(fn (Waybill $waybill) => ($waybill->odometer_end ?? 0) - ($waybill->odometer_start ?? 0)),
                    'fuel_liters' => $waybills->flatMap->fuelLogs->sum('liters'),
                    'fuel_cost' => $waybills->flatMap->fuelLogs->sum('cost'),
                ];
            })
            ->all();
    }

    private function applyDateRange(Builder $query, Request $request, string $column): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate($column, '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate($column, '<=', $request->date('date_to'));
        }
    }

    private function inDateRange(?string $date, Request $request): bool
    {
        if (! $date) {
            return false;
        }

        if ($request->filled('date_from') && $date < $request->query('date_from')) {
            return false;
        }

        if ($request->filled('date_to') && $date > $request->query('date_to')) {
            return false;
        }

        return true;
    }

    private function label(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return [
            'petrol' => 'Бензин',
            'gas' => 'Газ',
            'diesel' => 'Дизель',
            'planned' => 'Запланирован',
            'active' => 'Активен',
            'completed' => 'Завершен',
            'cancelled' => 'Отменен',
            'pending' => 'Ожидает',
            'approved' => 'Допущен',
            'rejected' => 'Отклонен',
            'pre_trip' => 'Предрейсовый',
            'post_trip' => 'Послерейсовый',
            'opened' => 'Открыт',
            'pre_med_requested' => 'Ожидает предрейсовый медосмотр',
            'pre_med_rejected' => 'Предрейсовый медосмотр отклонен',
            'pre_med_approved' => 'Предрейсовый медосмотр пройден',
            'pre_tech_requested' => 'Ожидает предрейсовый техосмотр',
            'pre_tech_rejected' => 'Предрейсовый техосмотр отклонен',
            'pre_tech_approved' => 'Предрейсовый техосмотр пройден',
            'initial_print_pending' => 'Ожидает первую печать',
            'initial_printed' => 'Первая печать выполнена',
            'shift_started' => 'Смена начата',
            'shift_in_progress' => 'Смена в процессе',
            'return_started' => 'Рейс завершен',
            'post_med_requested' => 'Ожидает послерейсовый медосмотр',
            'post_med_rejected' => 'Послерейсовый медосмотр отклонен',
            'post_med_approved' => 'Послерейсовый медосмотр пройден',
            'post_tech_requested' => 'Ожидает послерейсовый техосмотр',
            'post_tech_rejected' => 'Послерейсовый техосмотр отклонен',
            'post_tech_approved' => 'Послерейсовый техосмотр пройден',
            'final_print_pending' => 'Ожидает итоговую печать',
            'final_printed' => 'Итоговая печать выполнена',
            'closed' => 'Закрыт',
        ][$value] ?? $value;
    }
}
