# Конфигурация мобильного приложения

API backend по умолчанию:

```text
http://localhost:8000/api
```

Для Android-эмулятора обычно нужен адрес:

```text
http://10.0.2.2:8000/api
```

Запуск на Android-эмуляторе:

```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api
```

Запуск на iOS Simulator или desktop:

```bash
flutter run --dart-define=API_BASE_URL=http://localhost:8000/api
```

Сборка APK через Docker:

```bash
docker compose --profile mobile run --rm mobile-build
```

Для отправки GPS необходимо добавить системные разрешения платформы:

- Android: `ACCESS_FINE_LOCATION`, `ACCESS_COARSE_LOCATION`;
- iOS: `NSLocationWhenInUseUsageDescription`.

Приложение не поддерживает офлайн-режим. Все этапы workflow восстанавливаются с backend через `/api/mobile/workflow`.
