# ИС ООО «АЗЫК»

Внутренняя система управления автотранспортом предприятия.

## Состав

- `backend-api` — Laravel API.
- `admin-panel` — административная панель Vue 3.
- `mobile-driver-app` — мобильное приложение водителя Flutter.
- `ocr-service` — распознавание показаний одометра по фотографии.
- `docker-compose.yml` — серверное окружение: nginx, PHP-FPM, PostgreSQL, Redis, WebSocket, OCR, worker и админка.

## Требования

- Docker Desktop.
- Flutter SDK и Android SDK для сборки мобильного приложения.

PHP, Composer, PostgreSQL, Redis, nginx и Python локально устанавливать не требуется.

## Запуск серверной части

```bash
cd "/Users/ilsaf/Documents/вкр ил"
docker compose up -d --build
```

После запуска:

- админка через backend: `http://localhost`;
- API: `http://localhost/api`;
- OCR: `http://localhost:8001/health`;
- WebSocket: `localhost:8081`;
- PostgreSQL: `localhost:5432`;
- Redis: `localhost:6379`.

При первом запуске контейнер `backend-setup` выполняет миграции и заполняет тестовые данные. Первый запрос распознавания одометра может выполняться дольше обычного, потому что OCR-контейнер подготавливает модели.

## Сборка админки в backend

Production-сборка админки собирается одноразовым контейнером `admin-build` и отдаётся nginx по адресу `http://localhost`.

Отдельный dev-контейнер админки по умолчанию не запускается. В обычном режиме Vue-приложение только собирается и копируется в volume для nginx.

Если нужно открыть систему с другого устройства, создай файл `.env` в корне проекта:

```bash
cp .env.example .env
```

И укажи IP компьютера:

```env
ADMIN_HOST=IP_КОМПЬЮТЕРА
HTTP_PORT=80
ADMIN_API_URL=/api
ADMIN_WS_PORT=8081
```

После изменения `.env` пересобери админку:

```bash
docker compose up -d --build --force-recreate admin-build nginx
```

Если нужен dev-режим админки на `http://localhost:5173`, запусти профиль разработки:

```bash
docker compose --profile dev up -d admin-panel
```

## Доступы

Админка:

| Роль | Логин | Пароль |
| --- | --- | --- |
| Администратор | `admin` | `admin123` |
| Диспетчер | `dispatcher` | `dispatcher123` |
| Медик | `medic` | `medic123` |
| Механик | `mechanic` | `mechanic123` |

Мобильное приложение:

| Роль | Логин | Пароль |
| --- | --- | --- |
| Водитель | `driver1` | `driver123` |

## Мобильное приложение

Для телефона и компьютера в одной Wi-Fi сети сначала нужно узнать IP компьютера:

```bash
ipconfig getifaddr en0
```

Если команда ничего не вывела, можно попробовать другой интерфейс:

```bash
ipconfig getifaddr en1
```

Запуск на подключенном Android-устройстве:

```bash
cd "/Users/ilsaf/Documents/вкр ил/mobile-driver-app"
flutter pub get
flutter run -d DEVICE_ID --dart-define=API_BASE_URL=http://IP_КОМПЬЮТЕРА/api
```

Сборка APK:

```bash
cd "/Users/ilsaf/Documents/вкр ил/mobile-driver-app"
flutter pub get
flutter build apk --debug --dart-define=API_BASE_URL=http://IP_КОМПЬЮТЕРА/api
```

Готовый файл:

```text
mobile-driver-app/build/app/outputs/flutter-apk/app-debug.apk
```

Для Android-эмулятора вместо IP компьютера используется адрес `10.0.2.2`:

```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2/api
```

## Полезные команды

Пересобрать контейнеры:

```bash
docker compose up -d --build --force-recreate
```

Остановить проект:

```bash
docker compose down
```

Полностью пересоздать базу и служебные volume проекта:

```bash
docker compose down -v
docker compose up -d --build
```

Очистить Flutter-сборку:

```bash
cd "/Users/ilsaf/Documents/вкр ил/mobile-driver-app"
flutter clean
rm -rf .dart_tool android/.gradle android/.kotlin build
flutter pub get
```
