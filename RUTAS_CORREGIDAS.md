# Corrección de Rutas - URL Base Dinámico

## Problema Original
La aplicación tenía rutas hardcodeadas con `/sistema_gestion_datos/` en:
- Links de navegación
- Includes CSS
- Fetch calls a controllers
- Links a otras páginas

Esto causaba que en producción (cuando la app corre en `/`), todas las rutas devolvieran 404.

## Solución Implementada

### 1. Archivo de Configuración: `config/url_config.php`
Define automáticamente `BASE_URL` según el entorno:
- **Desarrollo**: `/sistema_gestion_datos/` (si existe esa ruta en SCRIPT_NAME)
- **Producción**: `/` (raíz del dominio)

### 2. Actualización de Archivos PHP

#### `views/layouts/header.php`
- Importa `config/url_config.php`
- Usa `<?= BASE_URL ?>` para CSS
- Usa `BASE_URL` en todos los `navItem()` calls

#### `views/layouts/footer.php`
- Importa `config/url_config.php`
- Inyecta `window.BASE_URL` en JavaScript global

#### `views/pages/dashboard.php` y `consulta_aprendiz.php`
- Importan `config/url_config.php`
- Usan `<?= BASE_URL ?>` en PHP
- Usan `${window.BASE_URL}` en fetch calls de JavaScript

### 3. JavaScript Global
En `footer.php`, se define:
```javascript
window.BASE_URL = '<?= BASE_URL ?? "/" ?>';
```

Todos los fetch() ahora pueden usar:
```javascript
fetch(window.BASE_URL + 'controllers/...')
// O con template literals:
fetch(`${window.BASE_URL}controllers/...`)
```

## Próximos Pasos para Completar

Los siguientes archivos aún necesitan la corrección:
- `views/pages/eliminacion_masiva.php`
- `views/pages/carga_masiva.php`
- `views/pages/fases_proyecto.php`
- `views/pages/dashboard_fases.php`

Para cada uno:
1. Agregar al inicio: `<?php require_once __DIR__ . '/../../../config/url_config.php'; ?>`
2. Reemplazar:
   - `fetch('/sistema_gestion_datos/` → `fetch(window.BASE_URL +`
   - `href="/sistema_gestion_datos/` → `href="<?= BASE_URL ?>`

## Verificación en VPS

En el navegador, abre:
```
https://gestiondatos.slscode.online/
```

Debería:
1. ✅ Cargar el dashboard
2. ✅ Mostrar CSS/JS correctamente
3. ✅ Navegar entre páginas sin 404
4. ✅ Las tablas y gráficos deben cargar datos

Si algo no aparece, abre la consola (F12) y verifica los errores de red.

---

**Nota**: El `window.BASE_URL` se inyecta automáticamente en `footer.php`, así que todos los fetch() de todas las páginas pueden usarlo sin hacer nada adicional.
