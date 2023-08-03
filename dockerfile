# Use the official PHP 8.1 image as the base image
FROM php:8.1-cli

# Set the working directory inside the container
WORKDIR /var/www/html

# Install system dependencies required for Laravel and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git

# Install PHP extensions required for Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the entire Laravel project to the container's working directory
COPY . .

# Install project dependencies using Composer
RUN composer install

# Expose the port on which Laravel serves the application (default is 8000)
EXPOSE 8002

# Command to start the development server using "php artisan serve"
CMD ["php", "artisan", "serve", "--host", "0.0.0.0", "--port", "8002"]
