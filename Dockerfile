FROM php:cli-alpine

ENV COMPOSER_HOME /composer
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV PATH /composer/vendor/bin:$PATH

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ARG VERSION

RUN composer global require madewithlove/htaccess:"$VERSION"

VOLUME ["/app"]
WORKDIR /app

ENTRYPOINT ["htaccess"]
