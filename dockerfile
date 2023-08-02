FROM php:8.1-apache

# Atualize as fontes do APT e instale as dependências necessárias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git

# Instale as extensões PHP necessárias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Defina o diretório de trabalho dentro do container para o diretório principal do projeto Laravel
WORKDIR /var/www/html/public

# Copie o código do seu projeto Laravel para o diretório de trabalho
COPY . .

# Defina as permissões corretas para o servidor web
RUN chown -R www-data:www-data /var/www/html/public

# Instale as dependências do Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install

# Exponha a porta do servidor web do Apache
EXPOSE 80

# Comando padrão para executar o servidor web Apache
CMD ["apache2-foreground"]
