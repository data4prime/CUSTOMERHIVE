# Usa un'immagine base di PHP 8.1 con tutti gli strumenti necessari
FROM php:8.1-apache

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

RUN a2enmod ssl
RUN a2enmod rewrite
RUN a2enmod headers


# Copia i file dell'applicazione
COPY . /var/www/html

#Copia i certificati
#COPY /etc/letsencrypt/live/staging.thecustomerhive.com/fullchain.pem ./docker/certificates/fullchain.pem
#COPY /etc/letsencrypt/live/staging.thecustomerhive.com/privkey.pem ./docker/certificates/privkey.pem






# Installa le dipendenze Composer

RUN composer install --no-dev --no-interaction

RUN mkdir -p /var/www/html/storage/framework/sessions
RUN mkdir -p /var/www/html/storage/framework/views
RUN mkdir -p /var/www/html/storage/framework/cache
RUN mkdir -p /var/www/html/bootstrap/cache


# Imposta i permessi corretti
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache





#Imposta i permessi corretti cin chmod 
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap

RUN php artisan cache:clear

# Genera la chiave dell'applicazione
RUN php artisan key:generate

# Storage link
RUN php artisan storage:link



COPY ./docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Esponi la porta di default di Laravel
EXPOSE 80

CMD ["apache2-foreground"]

