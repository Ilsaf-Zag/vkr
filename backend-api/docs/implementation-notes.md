# Заметки по реализации backend

Текущая папка содержит Laravel-каркас: bootstrap, config, public entrypoint, модели, миграции, маршруты, сервисы, middleware, события и PDF-шаблоны.

Локальный PHP/Composer не требуется. Первый запуск выполняется через Docker Compose:

```bash
docker compose up --build
```

Ключевая бизнес-логика уже разложена по файлам:

- `app/Services/WaybillStateService.php`;
- `app/Services/InspectionService.php`;
- `database/migrations`;
- `routes/api.php`;
- `resources/views/pdf`.
