<?php
/**
 * API: Procesar PDF de Proyecto Formativo SENA (GFPI-F-016)
 * Versión 2.0 — Extracción directa mediante Python + pdfplumber
 */
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');

verificar_rate_limit(15, 60, 'upload_pdf');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió archivo PDF válido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['pdf'];
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo debe ser PDF'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo no debe superar 10MB'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Directorio temporal dentro del proyecto ──
$tmpDir = __DIR__ . '/../tmp_pdf';
if (!is_dir($tmpDir)) {
    @mkdir($tmpDir, 0755, true);
}

// Nombre único para evitar colisiones
$tmpFile = $tmpDir . '/pdf_' . uniqid('', true) . '.pdf';

if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar el archivo PDF temporalmente'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Ejecutar extractor Python usando proc_open (aísla stdout de stderr y fuerza UTF-8) ──
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$pythonCmd = 'python3';
if ($isWindows) {
    $venvPy = __DIR__ . '/../.venv/Scripts/python.exe';
    $winPy  = 'C:\Users\Usuario\AppData\Local\Programs\Python\Python313\python.exe';
    if (file_exists($venvPy)) {
        $pythonCmd = $venvPy;
    } elseif (file_exists($winPy)) {
        $pythonCmd = $winPy;
    } else {
        $pythonCmd = 'python';
    }
}

$scriptPath = __DIR__ . '/scripts/extract_pdf.py';

$descriptors = [
    0 => ["pipe", "r"],
    1 => ["pipe", "w"],
    2 => ["pipe", "w"]
];

$cmdArray = [$pythonCmd, '-X', 'utf8', $scriptPath, $tmpFile];
$process  = proc_open($cmdArray, $descriptors, $pipes);

$stdout = '';
$stderr = '';

if (is_resource($process)) {
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
}

// Limpiar archivo temporal en PHP
@unlink($tmpFile);

$response = trim($stdout);

if (empty($response)) {
    http_response_code(500);
    $errDetalle = !empty($stderr) ? $stderr : 'No se recibió respuesta del script de extracción Python';
    echo json_encode([
        'error' => 'Error en la extracción del PDF: ' . mb_convert_encoding($errDetalle, 'UTF-8', 'UTF-8, CP1252, ISO-8859-1')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($response, true);

if (!$data || !is_array($data)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al decodificar JSON de extracción: ' . mb_substr($response, 0, 300)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!empty($data['error'])) {
    http_response_code(422);
    echo json_encode(['error' => $data['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

$registros = $data['registros'] ?? [];

// Mapear datos para el frontend (compatible con el formato previo)
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
    'total_paginas'    => 0,
    'texto_extraido'   => 'Extraído con Python pdfplumber (estructura de tabla preservada)',
    'datos_extraidos'  => [
        'informacion_basica' => $data['informacion_basica'] ?? [],
        'fases'              => $data['fases'] ?? [],
        'registros'          => $registros,
        'resumen'            => $data['resumen'] ?? [],
    ],
    'datos_mapeados'  => $mapped,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
