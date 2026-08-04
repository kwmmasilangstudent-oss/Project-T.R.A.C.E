FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql mysqli \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

EXPOSE 8000

CMD ["sh", "-c", "php -m | grep -E 'PDO|pdo_mysql|mysqli' || true; php -S 0.0.0.0:${PORT:-8000} -t ."]
