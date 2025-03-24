########################################################################################################################
# Prebuild: Sass

FROM node:18 AS sass_build
WORKDIR /app
COPY . /app
RUN npm install -g sass && sass --update /app/public --style compressed --no-source-map

########################################################################################################################
# Main build

FROM trafex/php-nginx:latest

# Add PHP extensions
USER root
RUN apk add --no-cache php84-pdo_mysql
USER nobody

# nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf

# supervisord config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy app (after sass build step)
COPY --chown=nobody --from=sass_build /app /var/www/html

# Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer install --optimize-autoloader --no-interaction --no-progress --no-dev

# Run database init
COPY docker-db-init.sh /usr/local/bin/docker-db-init.sh
USER root
RUN chmod +x /usr/local/bin/docker-db-init.sh
USER nobody
ENTRYPOINT ["/usr/local/bin/docker-db-init.sh"]

# Base command from trafex/php-nginx
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]