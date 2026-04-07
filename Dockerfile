FROM php:8.2-cli

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /app
COPY . /app/

RUN mkdir -p /app/uploads

CMD php -S 0.0.0.0:${PORT:-80} -t /app
