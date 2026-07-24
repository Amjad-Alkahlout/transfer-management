# Stage 1 - Build frontend
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY . .

RUN npm run build

# Stage 2 - Nginx
FROM nginx:1.29-alpine

WORKDIR /var/www/html

COPY . .

COPY --from=frontend /app/public/build ./public/build

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

RUN rm -f /etc/nginx/conf.d/default.conf.default

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
