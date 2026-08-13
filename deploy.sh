#!/bin/bash
# Script de despliegue automático — Sistema de Gestión de Datos

set -e   # Detener en cualquier error

echo "========================================"
echo "  Despliegue Sistema de Gestión de Datos"
echo "========================================"

# ── Resolver contraseña de BD ─────────────────────────────────────────────────
# Prioridad: variable de entorno del sistema > config/.env
if [ -n "$DB_PASS" ]; then
    DB_PASS_LOCAL="$DB_PASS"
else
    DB_PASS_LOCAL=$(grep '^DB_PASS=' config/.env 2>/dev/null | cut -d '=' -f2- | tr -d '\r' || true)
fi

# 1. Ajustar permisos
echo ""
echo "[1/5] Ajustando permisos locales..."
sudo chown -R $USER:$USER .

# 2. Obtener los últimos cambios de GitHub
echo ""
echo "[2/5] Obteniendo cambios de GitHub..."
git fetch origin

# 3. Forzar sincronización exacta con main
echo ""
echo "[3/5] Sincronizando con la rama main..."
git reset --hard origin/main

# 4. Reconstruir y levantar contenedores
echo ""
echo "[4/5] Reconstruyendo y levantando contenedores Docker..."
docker compose up -d --build

# 5. Listo
echo ""
echo "[5/5] Contenedores en línea."

echo ""
echo "========================================"
echo "✅ Despliegue completado exitosamente."
echo "========================================"
