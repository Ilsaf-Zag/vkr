FROM node:22-alpine

WORKDIR /app

# Копируем package.json и устанавливаем зависимости
COPY package*.json ./

# Устанавливаем все зависимости с флагом для ARM/разных архитектур
RUN npm install --include=optional

# Если ошибка сохраняется, добавить флаг force:
# RUN npm install --force --include=optional

# Копируем исходный код
COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]