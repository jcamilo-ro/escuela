FROM php:8.2-apache

# Extension necesaria para conectar PHP con MySQL usando PDO.
RUN docker-php-ext-install pdo_mysql
# Se activa por compatibilidad y crecimiento futuro del proyecto.
RUN a2enmod rewrite
# Evita advertencias de Apache al iniciar el contenedor.
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf && a2enconf servername

# Directorio donde Apache expone el proyecto.
WORKDIR /var/www/html
