FROM nginx:1.29-alpine

WORKDIR /var/www/html

COPY . .

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

RUN rm -f /etc/nginx/conf.d/default.conf.default

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
