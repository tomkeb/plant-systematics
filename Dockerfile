FROM dockette/web:php-84

# nginx serves public/ (the base image serves /srv/www)
COPY docker/nginx/site.conf /etc/nginx/sites.d/site.conf
RUN rm -rf /srv/www

WORKDIR /srv

# PHP dependencies first, so this layer is cached until composer.lock changes
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-progress --prefer-dist --no-autoloader --no-scripts

COPY . /srv
RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p var public/uploads/photos data/users \
    && chown -R www-data:www-data data var public/uploads
