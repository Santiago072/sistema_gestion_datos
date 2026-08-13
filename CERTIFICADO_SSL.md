# 🔐 Configuración de Certificado SSL/TLS - Sistema de Gestión de Datos

## 📋 Resumen

Este documento explica cómo habilitar HTTPS con certificado SSL automático usando **Caddy** (ya incluido en Docker).

---

## ✨ Ventajas de Caddy para SSL

✅ Certificados automáticos con Let's Encrypt  
✅ Renovación automática  
✅ NO necesitas instalar Nginx o Apache  
✅ Configuración simple  
✅ Zero downtime para renovaciones  

---

## 🚀 PASOS PARA HABILITAR HTTPS

### PASO 1: Asegurar que DNS apunta al VPS

Verifica que tu dominio `gestiondatos.siscodel.online` apunta a la IP de tu VPS:

```bash
# En tu VPS
nslookup gestiondatos.siscodel.online
# o
dig gestiondatos.siscodel.online

# Deberías ver tu IP: 2.25.154.18
```

Si no apunta, actualiza los registros DNS en tu proveedor de dominios (Namecheap, GoDaddy, etc.).

### PASO 2: Modificar Dockerfile

Edita `Dockerfile` y busca la sección del Caddyfile (línea ~62):

**Cambiar de:**
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

**A:**
```dockerfile
cat > /etc/caddy/Caddyfile << EOF
gestiondatos.siscodel.online {
    root * /var/www/html
    php_fastcgi 127.0.0.1:9000
    file_server
    try_files {path} {path}/ /index.php
    encode gzip
    
    # Redirigir HTTP a HTTPS automáticamente
    @http {
        protocol http
    }
    redir @http https://{host}{uri}
}
EOF
```

### PASO 3: Modificar docker-compose.yml

Agrega puerto 443 (HTTPS):

**Encontrar:**
```yaml
ports:
  - "8897:80"
```

**Cambiar a:**
```yaml
ports:
  - "8897:80"
  - "8443:443"
```

### PASO 4: Crear carpeta para certificados (opcional pero recomendado)

```bash
cd /home/usuario/apps/sistema_gestion_datos

# Crear directorio para almacenar certificados de Caddy
mkdir -p caddy_data

# Dar permisos
chmod 755 caddy_data
```

### PASO 5: Modificar docker-compose.yml nuevamente

Agregar volumen para certificados:

**En la sección de volúmenes de `app`:**
```yaml
volumes:
  - gestion_uploads:/var/www/html/tmp_uploads
  - gestion_pdf:/var/www/html/tmp_pdf
  - gestion_logs:/var/www/html/logs
  - caddy_data:/root/.local/share/caddy  # ← Agregar esta línea
```

### PASO 6: Crear volumen con nombre para Caddy

Al final del archivo `docker-compose.yml`, en la sección `volumes:`:

```yaml
volumes:
  gestion_uploads:
  gestion_pdf:
  gestion_logs:
  gestion_db_data:
  caddy_data:  # ← Agregar esta línea
```

### PASO 7: Desplegar con SSL

```bash
# En tu VPS, dentro de la carpeta del proyecto
cd /home/usuario/apps/sistema_gestion_datos

# Reconstruir y desplegar
docker compose down
docker compose up -d --build

# Esperar 30-60 segundos para que Caddy obtenga el certificado
sleep 30

# Ver logs
docker compose logs app
```

**Deberías ver algo como:**
```
[INFO] Autosave is disabled
[INFO] Serving initial config (load from /etc/caddy/Caddyfile)
[INFO] tls.obtain: Obtaining certificate [gestiondatos.siscodel.online]
[INFO] tls.obtain: Certificate obtained successfully
[INFO] Server started successfully
```

### PASO 8: Verificar HTTPS

En navegador:

```
https://gestiondatos.siscodel.online
```

Deberías ver ✅ **Conexión segura** (candado verde).

---

## 🔄 REDIRECCIÓN DE HTTP → HTTPS

Si quieres que `http://gestiondatos.siscodel.online` automáticamente redireccioné a HTTPS:

El Dockerfile que proporcioné ya lo hace con:
```
redir @http https://{host}{uri}
```

Pero necesitas que Caddy escuche BOTH en puerto 80 y 443. En `docker-compose.yml`:

```yaml
ports:
  - "80:80"        # HTTP
  - "443:443"      # HTTPS
  - "8897:80"      # Puerto alternativo (optional)
```

---

## 📊 VERIFICAR CERTIFICADO

### En navegador

1. Ve a `https://gestiondatos.siscodel.online`
2. Haz clic en el candado 🔒
3. Haz clic en "Certificado"
4. Deberías ver:
   - **Emisor:** Let's Encrypt
   - **Válido hasta:** Fecha futura (90 días)
   - **Dominio:** gestiondatos.siscodel.online

### Por CLI

```bash
# Verificar certificado
echo | openssl s_client -servername gestiondatos.siscodel.online -connect gestiondatos.siscodel.online:443 2>/dev/null | openssl x509 -noout -dates -issuer

# Deberías ver algo como:
# issuer=C = US, O = Let's Encrypt, CN = R3
# notBefore=Aug 13 12:00:00 2026 GMT
# notAfter=Nov 11 12:00:00 2026 GMT
```

---

## 🔄 RENOVACIÓN AUTOMÁTICA

Caddy renueva certificados automáticamente 30 días antes de expirar. **No necesitas hacer nada.**

Si quieres forzar renovación manual:

```bash
docker compose restart app
```

---

## 🚨 TROUBLESHOOTING SSL

### Error: "Certificate not available"

```bash
# Verificar logs
docker compose logs app | grep -i "tls\|certificate"

# Comunes:
# 1. DNS no apunta al VPS
#    → Espera 24h y reintenta
# 2. Puertos 80/443 no abiertos en VPS
#    → Abre firewall
# 3. Caddy no tiene permisos de escritura
#    → docker compose exec app chmod 777 /root/.local/share/caddy
```

### Error: "Connection refused" en puerto 443

```bash
# Verificar que Caddy está escuchando en 443
docker compose exec app netstat -tuln | grep 443

# Si no aparece, reconstruir
docker compose down
docker compose up -d --build
```

### Error: "Too many redirects"

Significa que tienes un loop redirect HTTP → HTTPS.

Verifica que en Caddyfile NO hay dos bloques conflictivos.

El correcto es:
```
gestiondatos.siscodel.online {
    # config aquí
}
```

NO dos bloques como:
```
:80 { ... }
gestiondatos.siscodel.online { ... }
```

---

## 💾 BACKUP DE CERTIFICADOS

Caddy almacena certificados en: `/root/.local/share/caddy`

Ya lo configuramos como volumen Docker, así que se persiste automáticamente.

Para hacer backup manual:

```bash
docker compose exec app bash -c "tar -czf /var/www/html/cert_backup_$(date +%Y%m%d).tar.gz /root/.local/share/caddy"

# Descargar
scp usuario@tu-vps.com:/var/www/html/cert_backup_*.tar.gz .
```

---

## 📱 RESULTADO FINAL

### Acceso:
```
❌ http://gestiondatos.siscodel.online        (redirige a HTTPS)
✅ https://gestiondatos.siscodel.online       (seguro)
```

### Headers de seguridad (ya incluidos en config):
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Strict-Transport-Security: max-age=31536000
```

---

## 🎓 COMPARATIVA: Con y Sin SSL

| Aspecto | Sin SSL | Con SSL |
|---|---|---|
| **URL** | http://gestiondatos.siscodel.online | https://gestiondatos.siscodel.online |
| **Datos encriptados** | ❌ Texto plano | ✅ Encriptados |
| **Indicador navegador** | ⚠️ No seguro | 🔒 Seguro |
| **SEO en Google** | Penalizado | ✅ Prioridad |
| **Certificado** | - | Let's Encrypt (automático) |

---

**Última actualización:** 13 de Agosto de 2026  
**Caddy Version:** 2.x+

Para más info: https://caddyserver.com/docs/getting-started
