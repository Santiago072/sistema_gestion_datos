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
echo "[4/5] Reconstruyendo y levantando contenedores Docker..."
docker compose up -d --build

# 5. Aplicar migraciones de base de datos
echo ""
echo "[5/5] Esperando a que MariaDB esté listo y aplicando migraciones..."
for i in {1..20}; do
  if docker compose exec -T gestion_datos_db sh -c 'mariadb-admin ping -h 127.0.0.1 --silent' 2>/dev/null; then
    echo "  -> Base de datos lista para recibir consultas."
    break
  fi
  echo "  -> Esperando a MariaDB... ($i/20)"
  sleep 2
done

echo "  -> Ejecutando migraciones SQL..."
docker compose exec -T gestion_datos_db sh -c 'mariadb -h 127.0.0.1 -u root -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' < sql/migracion_vps_actual.sql 2>/dev/null || \
docker compose exec -T gestion_datos_db sh -c 'mariadb -h 127.0.0.1 -u "$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' < sql/migracion_vps_actual.sql 2>/dev/null || \
echo "Nota: Si las credenciales son personalizadas, aplica la migración con: docker compose exec -i gestion_datos_db mysql -u root -p sena_juicios < sql/migracion_vps_actual.sql"

echo ""
echo "========================================"
echo "✅ Despliegue completado exitosamente."
echo "========================================"
