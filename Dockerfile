FROM composer:2@sha256:5946476338742b200bb9ff88f8be56275ddae4b3949c72305cb0dbf10cfcb760 AS composer

FROM php:8.3-fpm-alpine@sha256:9fcec48321d890240d700ccdc2b475420c87d398826e68c3d8830b8fca663e5c AS php-extensions

ARG PHPREDIS_VERSION=6.3.0
ARG PHPREDIS_SHA256=cb8f81df1a275599e4f8ddcfec7e1f65ed1953e6f5673649149fd680ebff4cad

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    oniguruma-dev \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_pgsql gd mbstring zip bcmath intl opcache

RUN curl --fail --show-error --location --retry 5 \
    "https://github.com/phpredis/phpredis/archive/refs/tags/${PHPREDIS_VERSION}.tar.gz" \
    --output /tmp/phpredis.tar.gz \
    && echo "${PHPREDIS_SHA256}  /tmp/phpredis.tar.gz" | sha256sum -c \
    && mkdir /tmp/phpredis \
    && tar --extract --gzip --file /tmp/phpredis.tar.gz \
        --directory /tmp/phpredis --strip-components=1 \
    && cd /tmp/phpredis \
    && phpize \
    && ./configure --enable-redis \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/phpredis /tmp/phpredis.tar.gz

FROM php-extensions AS php-dependencies

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock /app/

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-autoloader \
    --no-scripts \
    --no-interaction \
    --no-progress

FROM node:22-alpine@sha256:16e22a550f3863206a3f701448c45f7912c6896a62de43add43bb9c86130c3e2 AS frontend

WORKDIR /app

COPY package.json package-lock.json vite.config.js /app/

RUN npm ci

COPY app /app/app
COPY public /app/public
COPY resources /app/resources
COPY --from=php-dependencies /app/vendor /app/vendor

RUN npm run build

FROM php:8.3-fpm-alpine@sha256:9fcec48321d890240d700ccdc2b475420c87d398826e68c3d8830b8fca663e5c

ARG CACHE_BUST=20260803

WORKDIR /app

RUN apk add --no-cache \
    nginx \
    gettext-envsubst \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    libzip \
    icu-libs \
    libpq \
    oniguruma

COPY --from=php-extensions /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-extensions /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

COPY . /app
COPY --from=php-dependencies /app/vendor /app/vendor
COPY --from=frontend /app/public/build /app/public/build

RUN mkdir -p \
        storage/app/private \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        bootstrap/cache \
        /run/nginx \
        /etc/nginx/templates \
    && APP_ENV=testing composer dump-autoload --no-dev --optimize --no-interaction \
    && cp docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template \
    && chmod +x docker/start-container.sh \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && rm /usr/local/bin/composer

ENV PHP_FPM_PM=dynamic \
    PHP_FPM_PM_MAX_CHILDREN=16 \
    PHP_FPM_PM_START_SERVERS=4 \
    PHP_FPM_PM_MIN_SPARE_SERVERS=2 \
    PHP_FPM_PM_MAX_SPARE_SERVERS=6

RUN { \
    echo '[www]'; \
    echo 'user = www-data'; \
    echo 'group = www-data'; \
    echo 'listen = 127.0.0.1:9000'; \
    echo 'pm = '${PHP_FPM_PM}; \
    echo 'pm.max_children = '${PHP_FPM_PM_MAX_CHILDREN}; \
    echo 'pm.start_servers = '${PHP_FPM_PM_START_SERVERS}; \
    echo 'pm.min_spare_servers = '${PHP_FPM_PM_MIN_SPARE_SERVERS}; \
    echo 'pm.max_spare_servers = '${PHP_FPM_PM_MAX_SPARE_SERVERS}; \
    echo 'catch_workers_output = yes'; \
    echo 'clear_env = no'; \
  } > /usr/local/etc/php-fpm.d/zz-jawla.conf

EXPOSE 8080

CMD ["/app/docker/start-container.sh"]
