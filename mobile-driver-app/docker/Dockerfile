# Flutter Web App Dockerfile
FROM ghcr.io/cirruslabs/flutter:stable

WORKDIR /app
ARG API_BASE_URL=http://localhost:8000/api

# Копируем приложение
COPY . .

# Включаем web-платформу
RUN flutter config --enable-web

# Получаем зависимости
RUN flutter pub get

# Собираем web-приложение
RUN flutter build web --release --dart-define=API_BASE_URL=${API_BASE_URL}

# Используем nginx для раздачи статики
FROM nginx:alpine

COPY --from=0 /app/build/web /usr/share/nginx/html

# Копируем конфиг nginx для SPA
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
