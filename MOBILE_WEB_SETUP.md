# 🚀 Запуск мобильного приложения в браузере через Docker

## Что я сделал:

1. **Создал Dockerfile** (`mobile-driver-app/docker/Dockerfile`)
   - Использует Flutter для сборки web-приложения
   - Собирает production-версию
   - Раздает через nginx

2. **Создал конфиг nginx** (`mobile-driver-app/docker/nginx.conf`)
   - Корректная маршрутизация для SPA (Single Page Application)
   - Проксирование API запросов на backend
   - Проксирование WebSocket

3. **Обновил docker-compose.yml**
   - Заменил dev-сервер на production-конфигурацию
   - Убрал профили (теперь запускается автоматически)
   - Добавил корректные зависимости

---

## ⚡ Как запустить:

### Вариант 1: Запустить всё вместе (backend + мобильное приложение)

```bash
cd "/Users/ilsaf/Documents/вкр ил"

# Запустить все сервисы (backend, БД, Redis, nginx, админка, мобильное приложение)
docker compose up -d --build
```

Затем откройте в браузере:
- **Мобильное приложение**: `http://localhost:8085`
- **Backend API**: `http://localhost:8000/api`
- **Админка**: `http://localhost:5173`

---

### Вариант 2: Только мобильное приложение (если backend уже работает)

```bash
cd "/Users/ilsaf/Documents/вкр ил"

# Запустить только мобильное приложение
docker compose up -d mobile-web --build
```

Откройте: `http://localhost:8085`

---

### Вариант 3: Пересобрать только мобильное приложение

```bash
cd "/Users/ilsaf/Documents/вкр ил"

# Пересобрать образ (без кэша)
docker compose build --no-cache mobile-web

# Запустить
docker compose up -d mobile-web
```

---

## 🔍 Дебаг / Просмотр логов:

```bash
# Логи мобильного приложения
docker compose logs -f mobile-web

# Логи всех сервисов
docker compose logs -f

# Войти в контейнер (если нужно)
docker exec -it azyk_mobile_web sh
```

---

## 🛑 Остановить контейнеры:

```bash
docker compose down
```

---

## ✅ Что вы увидите:

1. Flutter-приложение водителя в браузере
2. Экран авторизации
3. Workflow путевых листов, медосмотров, техосмотров, GPS, заправок

**Учетные данные** загружаются из backend (создаются через миграции БД или админку).

---

## ⚠️ Важно:

- **Backend должен быть запущен** на `http://localhost:8000`
- Убедитесь, что CORS настроены в Laravel (уже должны быть)
- Если используется Redis/WebSocket, они тоже должны быть запущены
- На macOS с Docker Desktop `host.docker.internal` работает автоматически

---

## 📁 Структура добавленных файлов:

```
mobile-driver-app/
├── docker/
│   ├── Dockerfile          ← новый (многоэтапная сборка)
│   └── nginx.conf          ← новый (конфиг веб-сервера)
└── ...
```

