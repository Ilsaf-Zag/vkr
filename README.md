# Развёртывание

Запуск серверной части:

```bash
docker compose up -d --build --force-recreate
```

API:

```text
Создать `.env` из `.env.example` и указать переменные
```

Пересборка административной панели:

```bash
docker compose rm -f admin-build
docker compose up -d --build --force-recreate admin-build nginx
```

Запуск мобильного приложения:

```bash
flutter pub get
flutter run -d DEVICE_ID --dart-define=API_BASE_URL=http://SERVER_HOST/api
```

Сборка мобильного приложения:

```bash
flutter pub get
flutter build apk --debug --dart-define=API_BASE_URL=http://SERVER_HOST/api
```