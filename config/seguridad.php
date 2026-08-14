<?php
/**
 * seguridad.php — Capa de Seguridad y Rate Limiting para Sistema de Gestión de Datos
 *
 * Funciones de protección:
 * - Anti-Saturación / DoS: Rate limiting por IP persistido en disco.
 * - Sanitización de datos de entrada sin corromper la BD.
 * - Escape de salida contra ataques XSS.
 * - Cabeceras HTTP de seguridad (Clickjacking, MIME-Sniffing).
 * - Validadores de formatos numéricos y extensiones de archivo.
 */

// ── Cabeceras HTTP de Seguridad ──────────────────────────────────────────────

function enviar_cabeceras_seguridad(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// ── IP del Cliente ────────────────────────────────────────────────────────────

function obtener_ip_cliente(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip  = trim($ips[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

// ── Rate Limiting por IP (Persistido en disco) ────────────────────────────────

function verificar_rate_limit(int $limite = 20, int $ventanaSegundos = 60, string $accion = 'global'): void
{
    $ip           = obtener_ip_cliente();
    $tiempoActual = time();
    $dirStorage   = sys_get_temp_dir() . '/sena_rate_limit';

    if (!is_dir($dirStorage)) {
        @mkdir($dirStorage, 0755, true);
    }

    $hashFile   = md5($ip . '_' . $accion);
    $filePath   = $dirStorage . '/rl_' . $hashFile . '.json';
    $timestamps = [];

    if (file_exists($filePath)) {
        $content    = @file_get_contents($filePath);
        $decoded    = json_decode((string)$content, true);
        $timestamps = is_array($decoded) ? $decoded : [];
    }

    // Filtrar marcas de tiempo dentro de la ventana activa
    $timestamps = array_values(array_filter($timestamps, function ($ts) use ($tiempoActual, $ventanaSegundos) {
        return ($tiempoActual - (int)$ts) < $ventanaSegundos;
    }));

    if (count($timestamps) >= $limite) {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error'   => true,
            'message' => 'Ha superado el límite de peticiones permitido desde su dirección IP. Por favor espere un momento.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $timestamps[] = $tiempoActual;
    @file_put_contents($filePath, json_encode($timestamps), LOCK_EX);
}

// ── Sanitización y Escape ─────────────────────────────────────────────────────

/**
 * Limpia espacios y barras invertidas sin alterar caracteres especiales de la BD.
 */
function sanitizar_entrada($data): string
{
    return stripslashes(trim((string)$data));
}

/**
 * Escapa caracteres HTML para imprimir de forma segura en las vistas (Anti-XSS).
 */
function escapar_salida($data): string
{
    return htmlspecialchars((string)$data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Validadores ───────────────────────────────────────────────────────────────

function validar_numero($numero): bool
{
    return is_numeric($numero) && (int)$numero > 0;
}

function validar_extension_archivo(string $nombreArchivo, array $extensionesPermitidas): bool
{
    $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    return in_array($ext, $extensionesPermitidas, true);
}
