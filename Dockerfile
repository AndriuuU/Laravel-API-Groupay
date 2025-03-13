FROM richarvey/nginx-php-fpm:latest

# Instalar dependencias necesarias para PostgreSQL
RUN apk update && apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo_pgsql

# Copiar todo el proyecto
COPY . /var/www/html

# Dar permisos de ejecución al script de despliegue
RUN chmod +x /var/www/html/scripts/00-laravel-deploy.sh

# Instalar dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer puerto HTTP
EXPOSE 80

CMD ["/start.sh"]
