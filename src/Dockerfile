FROM php:8.2-apache

# Installa l'estensione PDO per connettersi a Postgres
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copia tutti i file del tuo progetto dentro la cartella del server web
COPY . /var/www/html/

# Configura Apache sulla porta dinamica di Railway
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

EXPOSE ${PORT}