FROM php:8.2-cli

WORKDIR /app

# Install MySQL PDO extension
RUN docker-php-ext-install pdo pdo_mysql

COPY . .

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000"]
