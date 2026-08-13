<?php
/**
 * Configuración de URLs base para la aplicación
 * Define BASE_URL automáticamente según el entorno
 */

// Detectar BASE_URL automáticamente
// En producción (sin /sistema_gestion_datos): BASE_URL = /
// En desarrollo local (con /sistema_gestion_datos): BASE_URL = /sistema_gestion_datos/

$current_file = __FILE__;
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// Detectar si estamos en la ruta del proyecto
if (strpos($script_name, '/sistema_gestion_datos/') !== false) {
    // Desarrollo local con /sistema_gestion_datos/
    define('BASE_URL', '/sistema_gestion_datos/');
} else {
    // Producción - raíz del dominio
    define('BASE_URL', '/');
}

// Para compatibilidad: rutas comunes
define('ASSETS_URL', BASE_URL . 'assets/');
define('CONTROLLERS_URL', BASE_URL . 'controllers/');
define('VIEWS_URL', BASE_URL . 'views/');
?>
