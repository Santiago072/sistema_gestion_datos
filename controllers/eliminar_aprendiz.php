<?php
/**
 * API: Eliminar un aprendiz específico de una ficha
 * POST /api/eliminar_aprendiz.php
 * Body: documento, id_ficha
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AprendizModel.php';

header('Content-Type: application/json; charset=utf-8');

verificar_rate_limit(30, 60, 'eliminar_aprendiz');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$documento = sanitizar_entrada($_POST['documento'] ?? '');
$id_ficha  = sanitizar_entrada($_POST['id_ficha']  ?? '');

if (!$documento || !$id_ficha) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Documento e id_ficha son obligatorios']);
    exit;
}

$model = new AprendizModel(getDB());

if (!$model->existeEnFicha($documento, $id_ficha)) {
    echo json_encode(['success' => false, 'message' => 'No se encontró el aprendiz en esa ficha']);
    exit;
}

try {
    $model->eliminar($documento, $id_ficha);
    echo json_encode([
        'success' => true,
        'message' => "Aprendiz $documento eliminado correctamente de la ficha $id_ficha"
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
