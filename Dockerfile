FROM richarvey/nginx-php-fpm:latest

# Instalar extensiones necesarias para PostgreSQL
RUN apk update && apk add --no-cache \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql

# Copiar el proyecto
COPY . /var/www/html

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

# Permisos necesarios
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Puerto dinámico de Render
EXPOSE 80

CMD ["/start.sh"]
