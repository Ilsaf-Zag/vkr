# Структура базы данных

Основная БД: PostgreSQL.

## Ключевые таблицы

| Таблица | Назначение |
|---|---|
| `users` | учетные записи всех пользователей |
| `drivers` | профиль водителя |
| `vehicles` | автомобили |
| `work_orders` | план-наряды |
| `waybills` | путевые листы |
| `medical_inspections` | медосмотры |
| `technical_inspections` | техосмотры |
| `fuel_logs` | заправки |
| `gps_points` | GPS-точки |
| `files` | фото и PDF-файлы |
| `waybill_odometer_captures` | фото одометра, OCR-результаты и подтвержденные значения |
| `audit_logs` | журнал действий |
| `personal_access_tokens` | токены Laravel Sanctum |

## Основные связи

```mermaid
erDiagram
    USERS ||--o| DRIVERS : has
    DRIVERS ||--o{ WORK_ORDERS : assigned
    VEHICLES ||--o{ WORK_ORDERS : assigned
    WORK_ORDERS ||--o| WAYBILLS : creates
    DRIVERS ||--o{ WAYBILLS : drives
    VEHICLES ||--o{ WAYBILLS : used
    WAYBILLS ||--o{ MEDICAL_INSPECTIONS : has
    WAYBILLS ||--o{ TECHNICAL_INSPECTIONS : has
    WAYBILLS ||--o{ FUEL_LOGS : has
    WAYBILLS ||--o{ GPS_POINTS : tracks
    WAYBILLS ||--o{ WAYBILL_ODOMETER_CAPTURES : has
    FILES ||--o{ WAYBILL_ODOMETER_CAPTURES : stores
    FILES ||--o{ DRIVERS : photo
    FILES ||--o{ VEHICLES : photo
```

## Ограничения

- `users.login` уникален.
- Водитель должен иметь учетную запись с ролью `driver`.
- На одного водителя допускается один активный план-наряд на дату и смену.
- Путевой лист создается только на основе план-наряда.
- Медосмотр и техосмотр имеют тип `pre_trip` или `post_trip`.
- Заправка может быть добавлена только во время активной смены.
- GPS-точки принимаются только для активного путевого листа.
- Для одного путевого листа допускается только одна фиксация одометра типа `start` и одна типа `finish`.
- Конечный подтвержденный одометр не может быть меньше начального.
