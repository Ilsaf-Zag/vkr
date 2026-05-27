# Диаграммы для ВКР

## Use-case diagram

Показать роли:

- администратор системы;
- диспетчер;
- медик;
- механик;
- водитель.

Основные варианты использования:

- управление пользователями;
- создание план-наряда;
- открытие путевого листа;
- прохождение медосмотра;
- прохождение техосмотра;
- начало и завершение смены;
- добавление заправки;
- просмотр карты;
- формирование отчетов.

## ERD

Показать таблицы:

- `users`;
- `drivers`;
- `vehicles`;
- `work_orders`;
- `waybills`;
- `medical_inspections`;
- `technical_inspections`;
- `fuel_logs`;
- `gps_points`;
- `files`;
- `audit_logs`.

## Sequence diagram

Рекомендуемый сценарий: открытие путевого листа и предрейсовые проверки.

Участники:

- водитель;
- мобильное приложение;
- backend API;
- медик;
- механик;
- web-админка;
- база данных.

## Activity diagram

Показать пошаговый workflow водителя от авторизации до закрытия смены.

## State machine diagram

Показать состояния путевого листа:

```text
opened -> pre_med_requested -> pre_med_approved
-> pre_tech_requested -> pre_tech_approved
-> initial_print_pending -> initial_printed
-> shift_started -> shift_in_progress
-> return_started -> post_med_requested -> post_med_approved
-> post_tech_requested -> post_tech_approved
-> final_print_pending -> final_printed -> closed
```

Также показать ветки отказа:

- `pre_med_rejected`;
- `pre_tech_rejected`;
- `post_med_rejected`;
- `post_tech_rejected`.

## Deployment diagram

Показать:

- мобильное приложение водителя;
- браузер сотрудника с админкой;
- backend API server;
- PostgreSQL;
- Redis;
- WebSocket server;
- файловое хранилище.

