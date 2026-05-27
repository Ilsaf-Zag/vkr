# REST API и WebSocket

## Auth API

| Метод | Endpoint | Доступ | Назначение |
|---|---|---|---|
| POST | `/api/auth/admin/login` | сотрудники | вход в web-админку |
| POST | `/api/auth/driver/login` | водитель | вход в мобильное приложение |
| POST | `/api/auth/logout` | все | выход и отзыв токена |
| GET | `/api/auth/me` | все | текущий пользователь |

## Mobile API

| Метод | Endpoint | Назначение |
|---|---|---|
| GET | `/api/mobile/current-work-order` | активный план-наряд |
| POST | `/api/mobile/waybills/open` | открыть путевой лист |
| GET | `/api/mobile/workflow` | восстановить текущий этап |
| POST | `/api/mobile/inspections/medical/request` | запросить медосмотр |
| POST | `/api/mobile/inspections/technical/request` | запросить техосмотр |
| POST | `/api/mobile/waybills/initial-print-done` | отметить первую печать |
| POST | `/api/mobile/shift/start` | начать смену |
| POST | `/api/mobile/fuel-logs` | добавить заправку |
| GET | `/api/mobile/fuel-logs` | список заправок текущего ПЛ |
| POST | `/api/mobile/shift/finish-trip` | предварительно завершить рейс |
| POST | `/api/mobile/waybills/final-print-done` | отметить итоговую печать |
| POST | `/api/mobile/shift/close` | закрыть смену |
| POST | `/api/mobile/gps-points` | отправить GPS-точку |
| POST | `/api/mobile/gps-points/batch` | отправить пачку GPS-точек |

## Admin API

| Метод | Endpoint | Назначение |
|---|---|---|
| GET | `/api/admin/dashboard` | показатели dashboard |
| CRUD | `/api/admin/users` | пользователи |
| POST | `/api/admin/users/{id}/change-password` | смена пароля |
| CRUD | `/api/admin/drivers` | водители |
| CRUD | `/api/admin/vehicles` | автомобили |
| CRUD | `/api/admin/work-orders` | план-наряды |
| GET | `/api/admin/waybills` | список путевых листов |
| GET | `/api/admin/waybills/{id}` | карточка путевого листа |
| GET | `/api/admin/waybills/{id}/pdf/initial` | PDF первой печати |
| GET | `/api/admin/waybills/{id}/pdf/final` | PDF итоговых данных |
| GET | `/api/admin/medical-inspections` | заявки и история медосмотров |
| POST | `/api/admin/medical-inspections/{id}/approve` | подтвердить медосмотр |
| POST | `/api/admin/medical-inspections/{id}/reject` | отклонить медосмотр |
| GET | `/api/admin/technical-inspections` | заявки и история техосмотров |
| POST | `/api/admin/technical-inspections/{id}/approve` | подтвердить техосмотр |
| POST | `/api/admin/technical-inspections/{id}/reject` | отклонить техосмотр |
| GET | `/api/admin/gps/current` | текущие координаты |
| GET | `/api/admin/gps/history` | история движения |
| GET | `/api/admin/fuel-logs` | заправки |
| GET | `/api/admin/audit-logs` | журнал действий |

## Reports API

| Метод | Endpoint | Назначение |
|---|---|---|
| GET | `/api/admin/reports/waybills` | отчет по путевым листам |
| GET | `/api/admin/reports/mileage` | отчет по пробегу |
| GET | `/api/admin/reports/fuel` | отчет по заправкам |
| GET | `/api/admin/reports/driver-shifts` | отчет по сменам |
| GET | `/api/admin/reports/medical-inspections` | отчет по медосмотрам |
| GET | `/api/admin/reports/technical-inspections` | отчет по техосмотрам |
| GET | `/api/admin/reports/vehicle-usage` | отчет по использованию автомобилей |
| GET | `/api/admin/reports/{type}/export` | экспорт Excel |

## WebSocket-события

| Событие | Получатели | Назначение |
|---|---|---|
| `vehicle.location.updated` | админка | обновление координат автомобиля |
| `medical.request.created` | медик, dashboard | новая заявка на медосмотр |
| `technical.request.created` | механик, dashboard | новая заявка на техосмотр |
| `inspection.status.changed` | водитель, админка | изменение статуса осмотра |
| `waybill.status.changed` | водитель, админка | изменение статуса путевого листа |
| `dashboard.metrics.updated` | админка | обновление счетчиков |

