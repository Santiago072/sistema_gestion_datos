# ⚡ Quick Start - Despliegue en CMD/VPS

## 🎯 Resumen Ultra-Rápido

Ejecuta estos comandos en tu VPS para desplegar el sistema:

---

## 1️⃣ CONECTAR AL VPS

```cmd
ssh usuario@tu-vps.com
```

O por IP:
```cmd
ssh usuario@2.25.154.18
```

---

## 2️⃣ NAVEGAR A LA CARPETA

```bash
cd /home/usuario/apps/sistema_gestion_datos
# o donde tengas el proyecto
```

---

## 3️⃣ CREAR ARCHIVO .env

```bash
cp .env.example .env
nano .env
```

Edita y pon una contraseña fuerte para `DB_PASS`. Luego Ctrl+O, Enter, Ctrl+X para guardar.

---

## 4️⃣ EJECUTAR DESPLIEGUE

```bash
chmod +x deploy.sh
bash deploy.sh
```

**Espera 60 segundos.**

---

## 5️⃣ VERIFICAR QUE FUNCIONA

```bash
docker ps
```

Deberías ver 2 contenedores: `gestion_datos_app` y `gestion_datos_db`

---

## 6️⃣ ACCEDER

Abre navegador:

```
http://gestiondatos.siscodel.online:8897
o
http://tu-vps.com:8897
```

---

## 🔐 AGREGAR SSL (HTTPS)

1. Edita `Dockerfile` línea ~62
2. Cambia `:${PORT}` por `gestiondatos.siscodel.online`
3. En `docker-compose.yml` agrega puerto `8443:443`
4. Ejecuta:

```bash
docker compose down
docker compose up -d --build
```

Espera 30 segundos. Caddy obtiene certificado automático.

Accede: `https://gestiondatos.siscodel.online`

---

## 📊 COMANDOS ÚTILES

```bash
# Ver logs
docker compose logs -f app

# Reiniciar
docker compose restart app

# Parar
docker compose down

# Levantar
docker compose up -d

# Backup BD
docker compose exec gestion_datos_db mysqldump -u sena_user -p sena_juicios > backup.sql

# Ver estado
docker ps
```

---

## ✅ Listo!

El sistema está corriendo en: `http://gestiondatos.siscodel.online:8897`

Con SSL: `https://gestiondatos.siscodel.online`

---

**Para más detalles:** Ver `DEPLOY_PASOS.md` y `CERTIFICADO_SSL.md`
