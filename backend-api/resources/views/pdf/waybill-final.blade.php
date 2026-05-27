<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Итоговые данные путевого листа {{ $waybill->number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 8px;
            text-align: center;
        }

        h2 {
            font-size: 13px;
            margin: 16px 0 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #374151;
            padding: 5px 6px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
            text-align: left;
        }

        .muted {
            color: #6b7280;
        }

        .signatures td {
            height: 42px;
        }
    </style>
</head>
<body>
    @php
        $mileage = $waybill->odometer_start !== null && $waybill->odometer_end !== null
            ? $waybill->odometer_end - $waybill->odometer_start
            : null;
        $fuelLiters = $waybill->fuelLogs->sum('liters');
        $fuelCost = $waybill->fuelLogs->sum('cost');
    @endphp

    <h1>Итоговые данные путевого листа</h1>

    <table>
        <tr>
            <th>Организация</th>
            <td>{{ $waybill->organization_name }}</td>
            <th>Номер</th>
            <td>{{ $waybill->number }}</td>
        </tr>
        <tr>
            <th>Водитель</th>
            <td>{{ $waybill->driver->full_name }}</td>
            <th>Автомобиль</th>
            <td>{{ $waybill->vehicle->brand }} {{ $waybill->vehicle->model }} / {{ $waybill->vehicle->plate_number }}</td>
        </tr>
    </table>

    <h2>Итоги смены</h2>
    <table>
        <tr>
            <th>Начало смены</th>
            <td>{{ optional($waybill->shift_started_at)->format('d.m.Y H:i') }}</td>
            <th>Возвращение</th>
            <td>{{ optional($waybill->shift_finished_at)->format('d.m.Y H:i') }}</td>
        </tr>
        <tr>
            <th>Одометр на начало</th>
            <td>{{ $waybill->odometer_start ?? 'Не указан' }}</td>
            <th>Одометр на конец</th>
            <td>{{ $waybill->odometer_end ?? 'Не указан' }}</td>
        </tr>
        <tr>
            <th>Пробег</th>
            <td>{{ $mileage ?? 'Не рассчитан' }}</td>
            <th>Маршрут</th>
            <td>{{ $waybill->route_name }}</td>
        </tr>
    </table>

    <h2>Заправки</h2>
    <table>
        <tr>
            <th>Дата</th>
            <th>Топливо</th>
            <th>Литры</th>
            <th>Стоимость</th>
            <th>Одометр</th>
        </tr>
        @forelse ($waybill->fuelLogs as $fuelLog)
            <tr>
                <td>{{ optional($fuelLog->fueled_at)->format('d.m.Y H:i') }}</td>
                <td>{{ $fuelLog->fuel_type->value }}</td>
                <td>{{ $fuelLog->liters }}</td>
                <td>{{ $fuelLog->cost }}</td>
                <td>{{ $fuelLog->odometer }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">Заправки не добавлялись</td>
            </tr>
        @endforelse
        <tr>
            <th colspan="2">Итого</th>
            <td>{{ $fuelLiters }}</td>
            <td>{{ $fuelCost }}</td>
            <td></td>
        </tr>
    </table>

    <h2>Послерейсовые проверки</h2>
    <table>
        <tr>
            <th>Проверка</th>
            <th>Статус</th>
            <th>Дата решения</th>
            <th>Ответственный</th>
        </tr>
        <tr>
            <td>Медицинский осмотр</td>
            <td>{{ optional($waybill->medicalInspections->where('type', 'post_trip')->first())->status?->value ?? 'не выполнен' }}</td>
            <td>{{ optional(optional($waybill->medicalInspections->where('type', 'post_trip')->first())->decided_at)->format('d.m.Y H:i') }}</td>
            <td>{{ optional(optional($waybill->medicalInspections->where('type', 'post_trip')->first())->medic)->full_name }}</td>
        </tr>
        <tr>
            <td>Технический осмотр</td>
            <td>{{ optional($waybill->technicalInspections->where('type', 'post_trip')->first())->status?->value ?? 'не выполнен' }}</td>
            <td>{{ optional(optional($waybill->technicalInspections->where('type', 'post_trip')->first())->decided_at)->format('d.m.Y H:i') }}</td>
            <td>{{ optional(optional($waybill->technicalInspections->where('type', 'post_trip')->first())->mechanic)->full_name }}</td>
        </tr>
    </table>

    <h2>Подписи</h2>
    <table class="signatures">
        <tr>
            <th>Водитель</th>
            <td></td>
            <th>Диспетчер</th>
            <td></td>
        </tr>
        <tr>
            <th>Медик</th>
            <td></td>
            <th>Механик</th>
            <td></td>
        </tr>
    </table>

    <p class="muted">Итоговая учебная форма для закрытия смены и хранения в информационной системе.</p>
</body>
</html>

