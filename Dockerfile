FROM php:8.4-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

# deps do sistema
RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libzip-dev libonig-dev libicu-dev libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# extensões PHP essenciais p/ Laravel
RUN docker-php-ext-install pdo pdo_mysql mbstring zip intl
RUN pecl install redis && docker-php-ext-enable redis

# Composer da imagem oficial
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www

# 1) Copia só composer.* para aproveitar cache
COPY composer.json composer.lock ./

# 2) Instala vendor SEM scripts (aqui ainda não há 'artisan')
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

# 3) Agora copia o restante do projeto (inclui 'artisan')
COPY . .

# 4) Opcional: gerar autoload otimizado e rodar scripts do composer
#    (o post-autoload-dump chama o package:discover)
RUN composer dump-autoload -o && \
    composer run-script post-autoload-dump || true

# (se quiser gerar a APP_KEY em build – só se já tiver .env presente)
# RUN php artisan key:generate --force || true

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
