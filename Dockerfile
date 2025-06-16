FROM php:8.2-apache

# Instala extensiones necesarias para MySQLi y sockets (Ratchet)
RUN docker-php-ext-install mysqli sockets

# Instala Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Copia el código fuente
COPY . /var/www/html/

# Da permisos correctos
RUN chown -R www-data:www-data /var/www/html

# Expón el puerto 80 para HTTP
EXPOSE 8080
