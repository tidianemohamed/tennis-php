FROM php:8.2-cli

# Installa le estensioni per Postgres e utility necessarie
RUN apt-get update && apt-get install -y libpq-dev unzip git \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Installa Composer globalmente nel container
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia tutti i file del progetto nella cartella di lavoro
COPY . /usr/src/app
WORKDIR /usr/src/app

# Installa le dipendenze di Composer
RUN composer install --no-dev --optimize-autoloader

# Espone la porta dinamica
EXPOSE ${PORT}

# Avvia il server integrato di PHP puntando direttamente alla cartella public!
CMD php -S 0.0.0.0:$PORT -t public