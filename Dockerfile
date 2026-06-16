FROM php:8.2-apache

# Installa le estensioni per Postgres e utility necessarie
RUN apt-get update && apt-get install -y libpq-dev unzip git \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Installa Composer globalmente nel container
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia TUTTI i file del progetto (ora che siamo nella root principale)
COPY . /var/www/html/

# Si sposta nella cartella del server per lanciare composer
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Modifica la configurazione di Apache per usare la cartella /public come ROOT del sito
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Configura Apache sulla porta dinamica di Railway
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

EXPOSE ${PORT}