#!/bin/bash
# Script de despliegue automático — Sistema de Gestión de Datos

set -e   # Detener en cualquier error

echo "========================================"
echo "  Despliegue Sistema de Gestión de Datos"
echo "========================================"

# ── Resolver contraseña de BD ─────────────────────────────────────────────────
if [ -n "$DB_PASS" ]; then
    DB_PASS_LOCAL="$DB_PASS"
else
    DB_PASS_LOCAL=$(grep '^DB_PASS=' config/.env 2>/dev/null | cut -d '=' -f2- | tr -d '\r' || true)
fi

ROOT_PASS="${MYSQL_ROOT_PASSWORD:-root}"
DB_USER_NAME="${DB_USER:-sena_user}"

# 1. Ajustar permisos
echo ""
echo "[1/5] Ajustando permisos locales..."
sudo chown -R $USER:$USER .

# 2. Obtener los últimos cambios de GitHub
echo ""
echo "[2/5] Obteniendo cambios de GitHub..."
git fetch origin

# 3. Forzar sincronización exacta con master
echo ""
echo "[3/5] Sincronizando con la rama master..."
git reset --hard origin/master

# 4. Reconstruir y levantar contenedores
echo ""
echo "[4/5] Reconstruyendo y levantando contenedores Docker..."
docker compose up -d --build

# 5. Ejecutar migraciones pendientes de base de datos en el contenedor
echo ""
echo "[5/5] Aplicando migraciones de base de datos en gestion_datos_db..."
if [ -f "sql/migration_v4_proyectos_separados.sql" ]; then
    echo "  -> Copiando y ejecutando migration_v4_proyectos_separados.sql..."
    docker compose cp sql/migration_v4_proyectos_separados.sql gestion_datos_db:/tmp/migration_v4.sql
    docker compose exec -T gestion_datos_db sh -c '
        if mariadb -u root -p"'"$ROOT_PASS"'" sena_juicios < /tmp/migration_v4.sql 2>/dev/null; then
            echo "  ✓ Migración ejecutada con root."
        elif mariadb -u "'"$DB_USER_NAME"'" -p"'"$DB_PASS_LOCAL"'" sena_juicios < /tmp/migration_v4.sql 2>/dev/null; then
            echo "  ✓ Migración ejecutada con $DB_USER_NAME."
        else
            mariadb -u root sena_juicios < /tmp/migration_v4.sql || true
        fi
        rm -f /tmp/migration_v4.sql
    ' || true
fi

echo ""
echo "========================================"
echo "✅ Despliegue completado exitosamente."
echo "========================================"
