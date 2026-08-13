# 🚀 Pasos para Desplegar en VPS - Sistema de Gestión de Datos

## 📋 Tabla de Contenidos
1. [Acceso al VPS](#acceso-al-vps)
2. [Pasos de Despliegue](#pasos-de-despliegue)
3. [Certificado SSL/TLS (HTTPS)](#certificado-ssltls)
4. [Verificación Final](#verificación-final)

---

## 🔑 Acceso al VPS

### Opción 1: Acceso por SSH (Linux/Mac)

```bash
ssh usuario@tu-vps.com
# O con IP
ssh usuario@2.25.154.18
```

### Opción 2: Acceso por Terminal CMD (Windows)

```cmd
# En Windows 10/11 con OpenSSH instalado
ssh usuario@tu-vps.com

# O si usas PuTTY, abre terminal SSH en el programa
```

### Opción 3: Acceso por RDP (si tienes Windows Server)

```cmd
mstsc /v:tu-vps.com
```

---

## 📍 PASOS DE DESPLIEGUE (Ejecutar en VPS)

### PASO 1: Acceder a la carpeta del proyecto

```bash
# Si el proyecto está en /home/usuario/apps/
cd /home/usuario/apps/sistema_gestion_datos

# O si está en /var/www/
cd /var/www/sistema_gestion_datos

# Ver que estamos en el directorio correcto
pwd
ls -la
```

**Deberías ver:**
```
Dockerfile
docker-compose.yml
deploy.sh
.env.example
README.md
config/
controllers/
models/
views/
sql/
```

### PASO 2: Crear archivo .env con credenciales

```bash
# Copiar ejemplo
cp .env.example .env

# Editar con nano
nano .env
```

**Contenido de `.env` (para VPS):**
```env
DB_HOST=gestion_datos_db
DB_USER=sena_user
DB_PASS=TuContraseñaSuperSegura123!
DB_NAME=sena_juicios
APP_BASE=/
PORT=80
SESSION_LIFETIME=3600
COOKIE_SECURE=1
UPLOAD_MAX_SIZE=10485760
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,webp,pdf,xlsx
```

**Para guardar en nano:**
- Presiona `Ctrl + O`
- Presiona `Enter`
- Presiona `Ctrl + X`

### PASO 3: Verificar que Docker está instalado

```bash
docker --version
docker compose --version
```

**Deberías ver:**
```
Docker version 24.0.0 (o superior)
Docker Compose version 2.0.0 (o superior)
```

Si no está instalado:
```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
```

### PASO 4: Crear la red Docker compartida (una sola vez)

```bash
docker network create sodicol_network
```

Si ya existe, dice: `Error response from daemon: network with name sodicol_network already exists`

No es un problema, solo significa que ya existe.

### PASO 5: Dar permisos de ejecución a deploy.sh

```bash
chmod +x deploy.sh
```

### PASO 6: Ejecutar el despliegue

```bash
bash deploy.sh
```

**Este script hace automáticamente:**
1. ✅ Ajusta permisos
2. ✅ Obtiene cambios de GitHub: `git fetch origin`
3. ✅ Sincroniza con master: `git reset --hard origin/master`
4. ✅ Construye imagen Docker: `docker compose up -d --build`
5. ✅ Levanta los contenedores

**Espera 30-60 segundos para que Caddy inicie.**

### PASO 7: Verificar que está corriendo

```bash
# Ver contenedores activos
docker ps

# Deberías ver algo como:
# CONTAINER ID   IMAGE                    NAMES
# abc123def      gestion_datos_app:latest gestion_datos_app
# xyz789abc      mariadb:10.11            gestion_datos_db
```

### PASO 8: Ver logs para verificar que todo inició bien

```bash
# Logs de la app (Ctrl+C para salir)
docker compose logs -f app

# Deberías ver algo como:
# Iniciando Sistema de Gestión de Datos en puerto: 80
# PHP-FPM running
# Caddy running
```

### PASO 9: Verificar acceso por URL

Abre navegador y ve a:

```
http://tu-vps.com:8897
o
http://2.25.154.18:8897
o
http://gestiondatos.siscodel.online:8897
```

Si ves la página de login/dashboard → ✅ **Funciona!**

Si ves error → Revisa logs: `docker compose logs app`

---

## 🔒 CERTIFICADO SSL/TLS (HTTPS)

### Opción A: Certificado automático con Caddy (RECOMENDADO)

Caddy puede generar certificados automáticamente con Let's Encrypt. Solo necesitas un dominio y que apunte al VPS.

**Modificar `docker-compose.yml`:**

Busca la sección de puertos y agrega 443:

```yaml
ports:
  - "8897:80"
  - "8443:443"   # ← Agregar esta línea para HTTPS
```

**Crear archivo `.env` actualizado:**

```env
ACME_EMAIL=tu-email@example.com
```

**Modificar `Dockerfile`:**

Busca la sección de Caddyfile y reemplaza:

```dockerfile
cat > /etc/caddy/Caddyfile << EOF
:${PORT} {
    root * /var/www/html
    php_fastcgi 127.0.0.1:9000
    file_server
    try_files {path} {path}/ /index.php
}
EOF
```

Por:

```dockerfile
cat > /etc/caddy/Caddyfile << EOF
gestiondatos.siscodel.online {
    root * /var/www/html
    php_fastcgi 127.0.0.1:9000
    file_server
    try_files {path} {path}/ /index.php
    encode gzip
}
EOF
```

Luego:

```bash
docker compose down
docker compose up -d --build
```

Caddy obtendrá automáticamente el certificado.

### Opción B: Certificado manual con Certbot

```bash
# Instalar Certbot
sudo apt-get install certbot python3-certbot-nginx

# Generar certificado
sudo certbot certonly --standalone -d gestiondatos.siscodel.online

# Esto crea certificados en:
# /etc/letsencrypt/live/gestiondatos.siscodel.online/
```

---

## ✅ VERIFICACIÓN FINAL

### Checklist de Despliegue

```bash
# 1. ¿Contenedores corriendo?
docker ps | grep gestion

# 2. ¿BD accesible?
docker compose exec gestion_datos_db mysql -u sena_user -p -e "SELECT 1"

# 3. ¿Logs limpios?
docker compose logs app | tail -20

# 4. ¿Acceso web?
curl http://localhost:8897

# 5. ¿Conexión a BD desde PHP?
docker compose exec app bash -c "php -r \"try { \$db = new PDO('mysql:host=gestion_datos_db;dbname=sena_juicios', 'sena_user', 'tu-pass'); echo 'BD OK'; } catch (Exception \$e) { echo 'Error: ' . \$e->getMessage(); }\""
```

---

## 🐛 TROUBLESHOOTING

### Error: "Connection refused" en puerto 8897

```bash
# Verificar que Caddy está escuchando
docker compose logs app | grep -i caddy

# Esperar 15-20 segundos y reintentar

# Si sigue sin funcionar, reiniciar
docker compose restart app
```

### Error: "Base de datos no encontrada"

```bash
# Verificar MariaDB
docker compose logs gestion_datos_db

# Reiniciar BD
docker compose restart gestion_datos_db

# Esperar 10 segundos y verificar
docker compose exec gestion_datos_db mysql -u sena_user -p sena_juicios -e "SHOW TABLES;"
```

### Error: "permission denied" en deploy.sh

```bash
# Dar permisos
chmod +x deploy.sh
bash deploy.sh
```

### Cambios no aparecen después de deploy

```bash
# Force pull de GitHub
docker compose exec app bash -c "cd /var/www/html && git fetch origin && git reset --hard origin/master"

# Reconstruir
docker compose down
docker compose up -d --build
```

---

## 📊 MONITOREO CONTINUO

### Ver logs en tiempo real

```bash
# App
docker compose logs -f app

# BD
docker compose logs -f gestion_datos_db

# Todo
docker compose logs -f
```

### Espacio en disco

```bash
docker system df
```

### Backup de BD (manual)

```bash
docker compose exec gestion_datos_db mysqldump \
  -u sena_user -p sena_juicios \
  > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar BD

```bash
docker compose exec -T gestion_datos_db \
  mysql -u sena_user -p sena_juicios < backup_20260813_143022.sql
```

---

## 🔄 ACTUALIZAR CÓDIGO DESDE GITHUB

Cuando hayas hecho cambios en GitHub:

```bash
cd /home/usuario/apps/sistema_gestion_datos
bash deploy.sh
```

Eso es. Todo se actualiza automáticamente.

---

## 📱 ACCESO DESDE CELULAR / REMOTO

```
URL: http://gestiondatos.siscodel.online:8897
o   http://2.25.154.18:8897
```

Si tienes HTTPS:
```
URL: https://gestiondatos.siscodel.online
```

---

**Última actualización:** 13 de Agosto de 2026  
**Versión:** 1.0

¿Preguntas? Revisa DOCKER_SETUP.md para más detalles técnicos.
