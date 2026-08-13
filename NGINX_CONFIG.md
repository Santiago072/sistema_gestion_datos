# Configuración de Nginx para gestiondatos.slscode.online

## Pasos a ejecutar en el VPS:

### 1. Crear el archivo de configuración de Nginx:
```bash
sudo bash -c 'cat > /etc/nginx/sites-available/gestiondatos.slscode.online << EOF
server {
    listen 80;
    listen [::]:80;
    server_name gestiondatos.slscode.online;

    location / {
        proxy_pass http://127.0.0.1:8898;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_buffering off;
    }
}
EOF
'
```

### 2. Remover el enlace antiguo si existe:
```bash
sudo rm -f /etc/nginx/sites-enabled/gestiondatos.slscode.online
```

### 3. Crear el nuevo enlace simbólico:
```bash
sudo ln -s /etc/nginx/sites-available/gestiondatos.slscode.online /etc/nginx/sites-enabled/gestiondatos.slscode.online
```

### 4. Verificar la configuración:
```bash
sudo nginx -t
```

### 5. Reiniciar Nginx:
```bash
sudo systemctl restart nginx
```

### 6. Verificar que funciona:
```bash
curl http://gestiondatos.slscode.online/
```

### 7. Configurar certificado SSL con Certbot (opcional pero recomendado):
```bash
sudo certbot --nginx -d gestiondatos.slscode.online
```

---

**Nota:** El contenedor Docker corre en puerto 8898, Nginx hace proxy_pass a ese puerto.
