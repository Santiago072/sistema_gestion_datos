<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ProgramaModel.php';

header('Content-Type: application/json; charset=utf-8');

verificar_rate_limit(20, 60, 'eliminar_programa');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_ficha = $_POST['id_ficha'] ?? '';

if (empty($id_ficha) || !validar_numero($id_ficha)) {
    echo json_encode(['success' => false, 'message' => 'ID de ficha no proporcionado']);
    exit;
}

$model = new ProgramaModel(getDB());

try {
    $model->eliminar($id_ficha);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
