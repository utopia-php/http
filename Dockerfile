FROM composer:2.0 AS step0


ARG TESTING=true

ENV TESTING=$TESTING

WORKDIR /usr/local/src/

COPY composer.* /usr/local/src/

RUN composer update --ignore-platform-reqs --optimize-autoloader \
    --no-plugins --no-scripts --prefer-dist \
    `if [ "$TESTING" != "true" ]; then echo "--no-dev"; fi`

FROM php:8.0-cli-alpine as final
LABEL maintainer="team@appwrite.io"

ENV DEBIAN_FRONTEND=noninteractive \
    PHP_VERSION=8

RUN \
  apk add --no-cache --virtual .deps \
  supervisor php$PHP_VERSION php$PHP_VERSION-fpm nginx bash


# Nginx Configuration (with self-signed ssl certificates)
COPY ./tests/docker/nginx.conf /etc/nginx/nginx.conf

# PHP Configuration
RUN mkdir -p /var/run/php
COPY ./tests/docker/www.conf /etc/php/$PHP_VERSION/fpm/pool.d/www.conf

# Script
COPY ./tests/docker/start /usr/local/bin/start

# Add PHP Source Code
COPY ./src /usr/share/nginx/html/src
COPY ./tests /usr/share/nginx/html/tests
COPY ./phpunit.xml /usr/share/nginx/html/phpunit.xml
COPY ./psalm.xml /usr/share/nginx/html/psalm.xml
COPY --from=step0 /usr/local/src/vendor /usr/share/nginx/html/vendor

# Supervisord Conf
COPY ./tests/docker/supervisord.conf /etc/supervisord.conf

# Executables
RUN chmod +x /usr/local/bin/start

EXPOSE 80

WORKDIR /usr/share/nginx/html

CMD ["/bin/bash", "/usr/local/bin/start"]
