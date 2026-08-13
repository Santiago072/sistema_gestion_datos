<?php
/**
 * API: Procesar PDF de Proyecto Formativo SENA (GFPI-F-016)
 * Versión 2.0 — Extracción directa mediante Python + pdfplumber
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
    @mkdir($tmpDir, 0755, true);
}

// Nombre único para evitar colisiones
$tmpFile = $tmpDir . '/pdf_' . uniqid('', true) . '.pdf';

if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar el archivo PDF temporalmente']);
    exit;
}

// ── Ejecutar extractor Python de forma directa (CLI) ──
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$pythonCmd = 'python3';
if ($isWindows) {
    $pythonExe = 'C:\Users\Usuario\AppData\Local\Programs\Python\Python313\python.exe';
    $pythonCmd = file_exists($pythonExe) ? '"' . $pythonExe . '"' : 'python';
}

$scriptPath = __DIR__ . '/scripts/extract_pdf.py';
$command = "{$pythonCmd} " . escapeshellarg($scriptPath) . " " . escapeshellarg($tmpFile) . " 2>&1";

$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

// Limpiar archivo temporal en PHP
@unlink($tmpFile);

$response = implode("\n", $output);

if (empty($response)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se recibió respuesta del script de extracción Python']);
    exit;
}

$data = json_decode($response, true);

if (!$data || !is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la extracción del PDF: ' . mb_substr($response, 0, 300)]);
    exit;
}

if (!empty($data['error'])) {
    http_response_code(422);
    echo json_encode(['error' => $data['error']]);
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
