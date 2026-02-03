FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# install dependencies
RUN apk add --no-cache \
      nginx \
      sqlite \
      sqlite-dev \
      $PHPIZE_DEPS \
  # Build,enable PDO SQLite; pdo is already built-in
  && docker-php-ext-install pdo_sqlite \
  # Remove build deps to keep the image small
  && apk del $PHPIZE_DEPS sqlite-dev

COPY . /var/www/html

COPY nginx.conf /etc/nginx/http.d/default.conf

# Permissions for sqlite file creation
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Start both services
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
