<?php
/**
 * API: Procesar PDF de Proyecto Formativo SENA (GFPI-F-016)
 * Versión Simplificada para Docker - Sin dependencias externas
 * 
 * Almacena el PDF y retorna información básica sin extracción de tablas complejas.
 */
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió archivo PDF válido']);
    exit;
}

$file = $_FILES['pdf'];
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo debe ser PDF']);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo no debe superar 10MB']);
    exit;
}

// ── Directorio para almacenar PDFs ──
$pdfDir = __DIR__ . '/../tmp_pdf';
if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0755, true);
}

// Nombre único para evitar colisiones
$filename = time() . '_' . uniqid() . '.pdf';
$filePath = $pdfDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar el archivo PDF']);
    exit;
}

// ── Información básica (sin extracción de tabla) ──
// En un ambiente Docker sin Python, devolvemos estructura genérica
// que permite al usuario confirmar la carga y luego completar manualmente si es necesario

$result = [
    'ok' => true,
    'archivo' => $filename,
    'mensaje' => 'PDF cargado correctamente. Los datos serán procesados manualmente o mediante el panel de administración.',
    'tamaño_bytes' => filesize($filePath),
    'datos_extraidos' => [
        'informacion_basica' => [
            'archivo_nombre' => $file['name'],
            'archivo_tamaño_kb' => round(filesize($filePath) / 1024, 2),
            'fecha_carga' => date('Y-m-d H:i:s'),
        ],
        'fases' => [
            ['nombre_fase' => 'ANÁLISIS', 'orden' => 1, 'descripcion' => 'Fase de análisis del proyecto formativo'],
            ['nombre_fase' => 'PLANEACIÓN', 'orden' => 2, 'descripcion' => 'Fase de planeación del proyecto formativo'],
            ['nombre_fase' => 'EJECUCIÓN', 'orden' => 3, 'descripcion' => 'Fase de ejecución del proyecto formativo'],
            ['nombre_fase' => 'EVALUACIÓN', 'orden' => 4, 'descripcion' => 'Fase de evaluación del proyecto formativo'],
        ],
        'actividades' => [],
        'registros' => [],
        'resumen' => [
            'total_fases' => 4,
            'total_actividades' => 0,
            'total_competencias' => 0,
            'total_resultados' => 0,
            'total_registros' => 0,
            'nota' => 'PDF almacenado. Complete manualmente o procese desde el panel administrativo.',
        ],
    ],
    'datos_mapeados' => [
        'fases' => [
            ['nombre_fase' => 'ANÁLISIS', 'orden' => 1, 'descripcion' => 'Fase de análisis del proyecto formativo'],
            ['nombre_fase' => 'PLANEACIÓN', 'orden' => 2, 'descripcion' => 'Fase de planeación del proyecto formativo'],
            ['nombre_fase' => 'EJECUCIÓN', 'orden' => 3, 'descripcion' => 'Fase de ejecución del proyecto formativo'],
            ['nombre_fase' => 'EVALUACIÓN', 'orden' => 4, 'descripcion' => 'Fase de evaluación del proyecto formativo'],
        ],
        'actividades' => [],
        'competencias' => [],
        'resultados' => [],
        'registros' => [],
        'resumen' => [
            'total_fases' => 4,
            'total_actividades' => 0,
            'total_competencias' => 0,
            'total_resultados' => 0,
            'total_registros' => 0,
        ],
    ],
];

http_response_code(200);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
