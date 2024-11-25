# Usa un'immagine base di PHP 8.1 con tutti gli strumenti necessari
FROM php:8.1-cli

ARG APP_USER=www
RUN groupadd -r ${APP_USER} && useradd --no-log-init -r -g ${APP_USER} ${APP_USER}

# Imposta la directory di lavoro
WORKDIR /var/www/html

# Installa le dipendenze di sistema necessarie
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libmcrypt-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    # Aggiungiamo nodejs e npm per asset frontend
    nodejs \
    npm

# Installa le estensioni PHP necessarie per Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Installa Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia i file dell'applicazione
COPY . /var/www/html

RUN php artisan cache:clear
RUN php artisan config:clear
RUN php artisan view:clear

RUN mkdir -p /var/www/html/storage/framework/sessions
RUN mkdir -p /var/www/html/storage/framework/views
RUN mkdir -p /var/www/html/storage/framework/cache
RUN mkdir -p /var/www/html/bootstrap/cache



# Imposta i permessi corretti
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache





#Imposta i permessi corretti cin chmod 
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap

# Installa le dipendenze Composer
#RUN composer install --no-interaction --optimize-autoloader --no-dev
RUN composer install



# Genera la chiave dell'applicazione
RUN php artisan key:generate

# Storage link
RUN php artisan storage:link

# Esegui le migrazioni del database
#RUN php artisan migrate --force
#RUN php artisan db:seed

# Esponi la porta di default di Laravel
EXPOSE 80


# Avvia il server di sviluppo
#CMD php artisan serve --host=0.0.0.0 --port=80

#CMD php artisan migrate --seed

# Script di avvio
#COPY ./docker/scripts/entrypoint.sh /usr/local/bin/
#RUN chmod +x /usr/local/bin/entrypoint.sh
CMD php artisan serve --host=0.0.0.0 --port=80

#ENTRYPOINT ["entrypoint.sh"]