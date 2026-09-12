#!/bin/bash
# Script de despliegue automático — Sistema de Gestión de Datos

set -e   # Detener en cualquier error

echo "========================================"
echo "  Despliegue Sistema de Gestión de Datos"
echo "========================================"

# 1. Ajustar permisos locales
echo ""
echo "[1/4] Ajustando permisos locales..."
sudo chown -R $USER:$USER .

# 2. Obtener los últimos cambios de GitHub
echo ""
echo "[2/4] Obteniendo cambios de GitHub..."
git fetch origin master

# 3. Forzar sincronización exacta con master
echo ""
echo "[3/4] Sincronizando con la rama master..."
git reset --hard origin/master

# 4. Reconstruir y levantar contenedores
echo ""
echo "[4/4] Reconstruyendo y levantando contenedores Docker..."
docker compose up -d --build

echo ""
echo "========================================"
echo "✅ Despliegue completado exitosamente."
echo "========================================"
