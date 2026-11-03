FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql
COPY src/ /var/www/html/
COPY docker/uploads.conf /etc/apache2/conf-available/svwa-uploads.conf
RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads
RUN mkdir -p /var/lib/svwa-reset && chown www-data:www-data /var/lib/svwa-reset && chmod 700 /var/lib/svwa-reset
RUN printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf && a2enconf servername svwa-uploads
EXPOSE 80
