FROM php:8.2-apache

# Install the missing MySQL driver
RUN docker-php-ext-install pdo pdo_mysql

# Align www-data with your host's user ID to eliminate the permission ping-pong
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data
