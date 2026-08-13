# 🐳 Configuración Docker - Sistema de Gestión de Datos

## 📋 Resumen

Este documento describe cómo desplegar el **Sistema de Gestión de Datos (SENA Juicios Evaluativos)** usando Docker, siguiendo la misma arquitectura que los otros sistemas ya desplegados en el VPS.

---

## 🏗️ Arquitectura Docker

```
┌─────────────────────────────────────────────────────┐
│             Tu VPS (Linux / Ubuntu)                 │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │    Docker Container 1: gestion_datos_app   │   │
│  │  • PHP 8.2 + Caddy (puerto 80)             │   │
│  │  • Tu código PHP                            │   │
│  │  • Conecta a MariaDB por red Docker         │   │
│  └─────────────────────────────────────────────┘   │
│           ↓                                         │
│  ┌─────────────────────────────────────────────┐   │
│  │   Docker Container 2: gestion_datos_db     │   │
│  │  • MariaDB 10.11 (puerto 3306 interno)     │   │
│  │  • Base de datos: sena_juicios             │   │
│  │  • Volumen persistente: gestion_db_data    │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  Volúmenes (Almacenamiento Persistente):           │
│  • gestion_uploads/  → Archivos subidos            │
│  • gestion_pdf/      → PDFs generados              │
│  • gestion_logs/     → Logs de la aplicación       │
│                                                     │
└─────────────────────────────────────────────────────┘

Puerto expuesto al internet: 8896 (configurable)
```

---

## 🚀 Instalación en VPS

### Paso 1: Clonar el repositorio

```bash
cd /home/usuario/apps  # O donde tengas tus proyectos
git clone https://github.com/TU_USUARIO/sistema_gestion_datos.git
cd sistema_gestion_datos
```

### Paso 2: Crear archivo .env para Docker

Copia `.env.example` a `.env` con credenciales de producción:

```bash
cp .env.example .env
```

Edita `.env`:

```bash
nano .env
```

Contiene:

```env
DB_HOST=gestion_datos_db          # Nombre interno del contenedor
DB_USER=sena_user                 # Usuario de BD
DB_PASS=TuContraseñaSegura123    # Cambia esto!
DB_NAME=sena_juicios              # Nombre de la BD
APP_BASE=/                        # En Docker siempre /
PORT=80                           # Puerto del contenedor (interno)
UPLOAD_MAX_SIZE=10485760          # 10 MB
COOKIE_SECURE=1                   # HTTPS habilitado
```

### Paso 3: Crear la red Docker compartida (una sola vez)

```bash
docker network create sodicol_network
```

(Si ya existe de otros proyectos, ignora este paso)

### Paso 4: Ejecutar deploy.sh

```bash
bash deploy.sh
```

Esto hace:
1. ✅ Obtiene cambios de GitHub
2. ✅ Sincroniza con rama main
3. ✅ Construye imagen Docker
4. ✅ Levanta contenedores
5. ✅ Base de datos se inicializa automáticamente

### Paso 5: Verificar que funciona

```bash
# Ver logs
docker compose logs -f app

# Ver contenedores corriendo
docker ps

# Acceder al navegador
http://tu-dominio.com:8896
# o
http://tu-vps.com:8896
```

---

## 🔧 Comandos Útiles

### Ver estado de contenedores

```bash
docker compose ps
```

### Ver logs en tiempo real

```bash
docker compose logs -f app        # Logs de la app
docker compose logs -f gestion_datos_db  # Logs de BD
docker compose logs -f            # Todos los logs
```

### Ejecutar comandos dentro del contenedor

```bash
# Entrar a shell de PHP
docker compose exec app bash

# Ejecutar comando
docker compose exec app php -v

# Conectarse a BD
docker compose exec gestion_datos_db mysql -u sena_user -p sena_juicios
```

### Backup de Base de Datos

```bash
docker compose exec gestion_datos_db mysqldump \
  -u sena_user -p sena_juicios \
  > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar Base de Datos

```bash
docker compose exec -T gestion_datos_db \
  mysql -u sena_user -p sena_juicios \
  < backup_20260813_143022.sql
```

### Detener contenedores (SIN perder datos)

```bash
docker compose down
```

### Reiniciar

```bash
docker compose up -d
```

---

## 📍 Puertos y DNS

| Servicio | Puerto Interno | Puerto VPS | DNS |
|---|---|---|---|
| Caddy (Web) | 80 | 8896 | gestiondatos.siscodel.online |
| MariaDB | 3306 | (interno) | - |

**Acceso desde internet:**
```
http://gestiondatos.siscodel.online:8896
# o si tienes Nginx delante
http://gestiondatos.siscodel.online
```

---

## 🔐 Variables de Entorno

Archivo `.env` (NO subir a Git):

```env
# Base de Datos
DB_HOST=gestion_datos_db
DB_USER=sena_user
DB_PASS=ContraseñaSuperSegura
DB_NAME=sena_juicios

# Aplicación
APP_BASE=/
PORT=80
SESSION_LIFETIME=3600
COOKIE_SECURE=1

# Archivos
UPLOAD_MAX_SIZE=10485760
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,webp,pdf,xlsx
```

---

## 📊 Diferencias entre XAMPP y Docker

| Aspecto | XAMPP (Local) | Docker (VPS) |
|---|---|---|
| **Base de Datos Host** | `localhost` | `gestion_datos_db` |
| **Puerto BD** | 3306 | (interno) |
| **URL Base** | `/sistema_gestion_datos/` | `/` |
| **Archivo .env** | `config/.env` | `.env` (raíz) |
| **Persistencia** | Archivos locales | Volúmenes Docker |
| **Restart** | Manual | Automático |

---

## 🚨 Troubleshooting

### Error: "Connection refused" en puerto 8896

```bash
# Verificar que Caddy está escuchando
docker compose logs app | tail -20

# Esperar 10-15 segundos después de `docker compose up`
# Caddy tarda en iniciar
```

### Error: "Base de datos no encontrada"

```bash
# Verificar que MariaDB está corriendo
docker ps | grep gestion_datos_db

# Si no está, levantarlo
docker compose up -d gestion_datos_db

# Ver logs de BD
docker compose logs gestion_datos_db
```

### Error: "Permisos denegados" en carpetas

```bash
# Resetear permisos
docker compose exec app bash -c \
  "chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html/tmp_*"
```

### Cambios no aparecen después de deploy

```bash
# Verificar que Git sincronizó
docker compose exec app bash -c "cd /var/www/html && git log --oneline | head -5"

# Si no está actualizado, forzar pull manual
docker compose exec app bash -c "cd /var/www/html && git fetch origin && git reset --hard origin/main"

# Luego, reconstruir
docker compose up -d --build
```

---

## 📁 Estructura de Carpetas en Docker

```
/var/www/html/
├── index.php              → Entry point
├── config/                → Configuración
├── controllers/           → Controladores
├── models/                → Modelos
├── services/              → Servicios
├── views/                 → Vistas
├── assets/                → CSS, JS, imágenes estáticas
├── tmp_uploads/           → Archivos subidos (volumen)
├── tmp_pdf/               → PDFs generados (volumen)
├── logs/                  → Logs de la app (volumen)
├── .env                   → Variables de entorno
└── Dockerfile             → Definición de imagen
```

---

## 🔄 Ciclo de Despliegue

```
1. Haces cambios localmente
   ├─ git add .
   ├─ git commit -m "tu cambio"
   └─ git push origin main

2. En VPS ejecutas
   ├─ bash deploy.sh
   ├─ Git fetch + reset --hard origin/main
   ├─ Docker rebuild
   └─ Contenedores up

3. Verificas
   ├─ docker compose logs -f
   ├─ curl http://localhost:8896
   └─ ✅ Listo!
```

---

## 📞 Soporte

Si algo no funciona:

1. Revisa los logs: `docker compose logs -f app`
2. Verifica que los contenedores corren: `docker ps`
3. Reinicia todo: `docker compose down && docker compose up -d --build`

---

**Última actualización:** 13 de Agosto de 2026  
**Versión Docker:** 1.0
