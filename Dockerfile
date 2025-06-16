FROM php:8.2-apache

# Instala extensiones necesarias para MySQLi y sockets (Ratchet)
RUN docker-php-ext-install mysqli sockets

# Instala herramientas necesarias para Composer (por si acaso)
RUN apt-get update && apt-get install -y unzip git curl

# Instala Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copia primero los archivos de Composer para aprovechar la cache
WORKDIR /var/www/html
COPY composer.json composer.lock ./

# Instala dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Copia el resto del código fuente
COPY . /var/www/html/

# Da permisos correctos
RUN chown -R www-data:www-data /var/www/html

# Instala supervisord
RUN apt-get update && apt-get install -y supervisor

# Copia la configuración de supervisord
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expón el puerto 8080 para HTTP
EXPOSE 8080

# Lanza Apache y el WebSocket con supervisord
CMD ["/usr/bin/supervisord"]
