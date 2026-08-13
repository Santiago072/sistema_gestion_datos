FROM php:8.2-fpm

# Instalar dependencias del sistema para extensiones PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli pdo pdo_mysql gd mbstring intl zip

# Instalar Python3 y libreria pdfplumber para extracción de PDF
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    && pip3 install --no-cache-dir --break-system-packages pdfplumber \
    && rm -rf /var/lib/apt/lists/*

# Instalar Caddy, Composer y dependencias del sistema
RUN apt-get update && apt-get install -y \
    debian-keyring \
    debian-archive-keyring \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    gettext-base \
    git \
    unzip \
    && curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg \
    && curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list \
    && apt-get update && apt-get install -y caddy \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Copiar solo los archivos que existan
COPY . .

# Instalar dependencias PHP solo si composer.json existe
RUN if [ -f composer.json ]; then composer install --no-dev --no-autoloader --no-interaction; fi

# Crear carpetas con permisos correctos
RUN mkdir -p /var/www/html/logs \
    && mkdir -p /var/www/html/tmp_uploads \
    && mkdir -p /var/www/html/tmp_pdf \
    && mkdir -p /var/www/html/sessions \
    && chown -R www-data:www-data /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/tmp_uploads \
    && chown -R www-data:www-data /var/www/html/tmp_pdf \
    && chown -R www-data:www-data /var/www/html/sessions \
    && chown -R www-data:www-data /var/www/html \
    && chmod 755 /var/www/html/tmp_uploads \
    && chmod 755 /var/www/html/tmp_pdf \
    && chmod 755 /var/www/html/sessions

# Script de inicio con Caddy + PHP-FPM
RUN echo '#!/bin/bash\n\
PORT=${PORT:-80}\n\
echo "Iniciando Sistema de Gestión de Datos en puerto: $PORT"\n\
cat > /etc/caddy/Caddyfile << EOF\n\
:${PORT} {\n\
    root * /var/www/html\n\
    php_fastcgi 127.0.0.1:9000\n\
    file_server\n\
    try_files {path} {path}/ /index.php\n\
}\n\
EOF\n\
php-fpm --daemonize\n\
caddy run --config /etc/caddy/Caddyfile --adapter caddyfile' > /start.sh && chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
