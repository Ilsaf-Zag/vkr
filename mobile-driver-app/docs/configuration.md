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

Сборка Android APK:

```bash
flutter pub get
flutter build apk --debug --dart-define=API_BASE_URL=http://10.0.2.2:8000/api
```

Для реального телефона укажите IP компьютера:

```bash
flutter build apk --debug --dart-define=API_BASE_URL=http://192.168.1.25:8000/api
```

Готовый APK:

```text
mobile-driver-app/build/app/outputs/flutter-apk/app-debug.apk
```

Для отправки GPS необходимо добавить системные разрешения платформы:

- Android: `ACCESS_FINE_LOCATION`, `ACCESS_COARSE_LOCATION`;
- iOS: `NSLocationWhenInUseUsageDescription`.

Для фото одометра на Android добавлены разрешения камеры и выбора изображений.

Приложение не поддерживает офлайн-режим. Все этапы workflow восстанавливаются с backend через `/api/mobile/workflow`.
