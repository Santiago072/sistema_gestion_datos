<?php
/**
 * API: Importar datos del PDF procesado a la BD
 * v3 — Refactorizado con Clean Architecture (FasesImportService) y Bulk Insert
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/import/FasesImportService.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido o vacío']);
    exit;
}

$db      = getDB();
$idFicha = !empty($data['id_ficha']) ? (int)$data['id_ficha'] : null;

$service = new \Services\Import\FasesImportService($db);
$results = $service->import($data, $idFicha);

if ($results['ok']) {
    echo json_encode($results, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => implode(', ', $results['errores']) ?: 'Error desconocido al importar',
    ], JSON_UNESCAPED_UNICODE);
}
