FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql
RUN a2enmod rewrite
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf && a2enconf servername

WORKDIR /var/www/html
