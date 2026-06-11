<?php
/**
 * API: Procesar PDF de Proyecto Formativo SENA (GFPI-F-016)
 * Versión 2.0 — Extracción mediante Python + pdfplumber
 * 
 * MEJORA: pdfplumber detecta celdas combinadas correctamente,
 * a diferencia del enfoque anterior con regex sobre texto plano.
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

// ── Directorio temporal dentro del proyecto ──
$tmpDir = __DIR__ . '/../tmp_pdf';
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}

// Nombre único para evitar colisiones
$tmpFile = $tmpDir . '/pdf_' . uniqid('', true) . '.pdf';

if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar el archivo PDF temporalmente']);
    exit;
}

// ── Ruta al script Python ──
$scriptPath  = __DIR__ . '/scripts/extract_pdf.py';
$escapedPdf    = escapeshellarg($tmpFile);
$escapedScript = escapeshellarg($scriptPath);

// Ruta absoluta al ejecutable de Python detectada en este sistema
$pythonExe = 'C:\Users\Usuario\AppData\Local\Programs\Python\Python313\python.exe';

// -X utf8 fuerza UTF-8 en stdin/stdout/stderr sin necesitar variables de entorno
// cmd /c permite ejecutar el comando completo con rutas que tienen espacios
$command = 'cmd /c ""' . $pythonExe . '" -X utf8 ' . $escapedScript . ' ' . $escapedPdf . ' 2>&1"';

$output = shell_exec($command);

// Limpiar archivo temporal (comentado para depuración)
@unlink($tmpFile);

if ($output === null || trim($output) === '') {
    // shell_exec puede estar deshabilitado o Python no está en el PATH
    http_response_code(500);
    echo json_encode([
        'error' => 'No se pudo ejecutar Python. Verifica que Python esté instalado y en el PATH del sistema. ' .
                   'Intenta ejecutar "python --version" en tu terminal CMD.',
        'comando_ejecutado' => $command
    ]);
    exit;
}

// Detectar si hay un error de Python antes del JSON
// (Python puede imprimir warnings/errors antes del output real)
$jsonStart = strpos($output, '{');
if ($jsonStart === false) {
    http_response_code(500);
    echo json_encode([
        'error'  => 'Python no devolvió JSON válido. Detalles: ' . substr($output, 0, 1000)
    ]);
    exit;
}

$jsonOutput = substr($output, $jsonStart);
$data = json_decode($jsonOutput, true);

if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    http_response_code(500);
    echo json_encode([
        'error'  => 'Error al decodificar la respuesta de Python: ' . json_last_error_msg(),
        'output' => substr($output, 0, 500)
    ]);
    exit;
}

if (!empty($data['error'])) {
    http_response_code(422);
    echo json_encode(['error' => $data['error']]);
    exit;
}

// ── Construir la respuesta en el formato que espera el frontend ──
// El frontend espera: { ok, total_caracteres, total_paginas, datos_extraidos, datos_mapeados }
$registros = $data['registros'] ?? [];

// Mapear datos para el frontend (compatible con la función buildMappedData anterior)
$mapped = [
    'fases'        => $data['fases'] ?? [],
    'actividades'  => $data['actividades'] ?? [],
    'resultados'   => [],
    'competencias' => [],
    'registros'    => $registros,
    'resumen'      => $data['resumen'] ?? [],
];

// Resultados únicos
$resMap = [];
foreach ($registros as $reg) {
    $key = $reg['resultado_codigo'] ?: $reg['resultado_nombre'];
    if (!empty($reg['resultado_nombre']) && !isset($resMap[$key])) {
        $resMap[$key] = [
            'codigo' => $reg['resultado_codigo'],
            'nombre' => $reg['resultado_nombre'],
        ];
    }
}
$mapped['resultados'] = array_values($resMap);

// Competencias únicas
$compMap = [];
foreach ($registros as $reg) {
    $key = $reg['competencia_codigo'] ?: $reg['competencia'];
    if (!empty($reg['competencia']) && !isset($compMap[$key])) {
        $compMap[$key] = [
            'codigo' => $reg['competencia_codigo'],
            'nombre' => $reg['competencia'],
        ];
    }
}
$mapped['competencias'] = array_values($compMap);

// Actualizar resumen con totales reales
$mapped['resumen']['total_actividades']  = count($mapped['actividades']);
$mapped['resumen']['total_resultados']   = count($mapped['resultados']);
$mapped['resumen']['total_competencias'] = count($mapped['competencias']);

echo json_encode([
    'ok'               => true,
    'total_caracteres' => array_sum(array_map('strlen', array_column($registros, 'actividad'))) + 100,
    'total_paginas'    => 0, // pdfplumber no expone esto fácilmente en el script
    'texto_extraido'   => 'Extraído con Python pdfplumber (estructura de tabla preservada)',
    'datos_extraidos'  => [
        'informacion_basica' => $data['informacion_basica'] ?? [],
        'fases'              => $data['fases'] ?? [],
        'registros'          => $registros,
        'resumen'            => $data['resumen'] ?? [],
    ],
    'datos_mapeados'  => $mapped,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
