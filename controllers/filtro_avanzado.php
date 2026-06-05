<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/JuiciosModel.php';

$model = new JuiciosModel(getDB());

$filters = [
    'programa' => $_GET['programa'] ?? null,
    'documento' => $_GET['documento'] ?? null,
    'estado' => $_GET['estado'] ?? null,
    'competencia' => $_GET['competencia'] ?? null,
    'resultado' => $_GET['resultado'] ?? null,
    'tipo_juicio' => $_GET['tipo_juicio'] ?? null,
];

$isCsv = (isset($_GET['format']) && $_GET['format'] === 'csv');

if ($isCsv) {
    $rows = $model->getFiltroAvanzadoCsv($filters);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="filtro_juicios_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    if (!empty($rows)) fputcsv($out, array_keys($rows[0]), ';');
    foreach ($rows as $r) fputcsv($out, $r, ';');
    fclose($out);
} else {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 15;
    
    $result = $model->getFiltroAvanzado($filters, $page, $limit);
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
