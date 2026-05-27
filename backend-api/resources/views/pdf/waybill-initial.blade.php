<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Путевой лист {{ $waybill->number }}</title>
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
    <h1>Путевой лист грузового автомобиля</h1>

    <table>
        <tr>
            <th>Организация</th>
            <td>{{ $waybill->organization_name }}</td>
            <th>Номер</th>
            <td>{{ $waybill->number }}</td>
        </tr>
        <tr>
            <th>Дата</th>
            <td>{{ optional($waybill->date)->format('d.m.Y') }}</td>
            <th>Время открытия</th>
            <td>{{ optional($waybill->opened_at)->format('d.m.Y H:i') }}</td>
        </tr>
    </table>

    <h2>Водитель и автомобиль</h2>
    <table>
        <tr>
            <th>Водитель</th>
            <td>{{ $waybill->driver->full_name }}</td>
            <th>Водительское удостоверение</th>
            <td>{{ $waybill->driver->license_number }}</td>
        </tr>
        <tr>
            <th>Автомобиль</th>
            <td>{{ $waybill->vehicle->brand }} {{ $waybill->vehicle->model }}</td>
            <th>Гос. номер</th>
            <td>{{ $waybill->vehicle->plate_number }}</td>
        </tr>
        <tr>
            <th>Маршрут</th>
            <td colspan="3">{{ $waybill->route_name }}</td>
        </tr>
    </table>

    <h2>Показатели перед выездом</h2>
    <table>
        <tr>
            <th>Одометр на начало</th>
            <td>{{ $waybill->odometer_start ?? 'Не указан' }}</td>
            <th>Топливо на начало</th>
            <td>{{ $waybill->fuel_start ?? 'Не указано' }}</td>
        </tr>
    </table>

    <h2>Предрейсовые проверки</h2>
    <table>
        <tr>
            <th>Проверка</th>
            <th>Статус</th>
            <th>Дата решения</th>
            <th>Ответственный</th>
        </tr>
        <tr>
            <td>Медицинский осмотр</td>
            <td>{{ optional($waybill->medicalInspections->where('type', 'pre_trip')->first())->status?->value ?? 'не выполнен' }}</td>
            <td>{{ optional(optional($waybill->medicalInspections->where('type', 'pre_trip')->first())->decided_at)->format('d.m.Y H:i') }}</td>
            <td>{{ optional(optional($waybill->medicalInspections->where('type', 'pre_trip')->first())->medic)->full_name }}</td>
        </tr>
        <tr>
            <td>Технический осмотр</td>
            <td>{{ optional($waybill->technicalInspections->where('type', 'pre_trip')->first())->status?->value ?? 'не выполнен' }}</td>
            <td>{{ optional(optional($waybill->technicalInspections->where('type', 'pre_trip')->first())->decided_at)->format('d.m.Y H:i') }}</td>
            <td>{{ optional(optional($waybill->technicalInspections->where('type', 'pre_trip')->first())->mechanic)->full_name }}</td>
        </tr>
    </table>

    <h2>Подписи</h2>
    <table class="signatures">
        <tr>
            <th>Диспетчер</th>
            <td></td>
            <th>Водитель</th>
            <td></td>
        </tr>
        <tr>
            <th>Медик</th>
            <td></td>
            <th>Механик</th>
            <td></td>
        </tr>
    </table>

    <p class="muted">Учебная форма путевого листа, разработанная для информационной системы ООО «АЗЫК».</p>
</body>
</html>

